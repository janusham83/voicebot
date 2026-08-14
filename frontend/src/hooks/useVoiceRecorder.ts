import { useCallback, useEffect, useRef, useState } from 'react'

export type RecorderErrorType = 'permission-denied' | 'no-microphone' | 'unsupported' | 'unknown'

interface UseVoiceRecorderResult {
  isRecording: boolean
  isSupported: boolean
  error: string | null
  errorType: RecorderErrorType | null
  startRecording: () => Promise<string | null>
  stopRecording: () => Promise<Blob | null>
}

const PREFERRED_MIME_TYPES = [
  'audio/webm;codecs=opus',
  'audio/webm',
  'audio/mp4',
  'audio/ogg;codecs=opus',
]

function pickSupportedMimeType(): string | undefined {
  if (typeof MediaRecorder === 'undefined') {
    return undefined
  }
  return PREFERRED_MIME_TYPES.find((type) => MediaRecorder.isTypeSupported(type))
}

/**
 * Wraps the browser MediaRecorder API for capturing a single voice clip at a time.
 */
export function useVoiceRecorder(): UseVoiceRecorderResult {
  const [isRecording, setIsRecording] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [errorType, setErrorType] = useState<RecorderErrorType | null>(null)

  const mediaRecorderRef = useRef<MediaRecorder | null>(null)
  const streamRef = useRef<MediaStream | null>(null)
  const chunksRef = useRef<Blob[]>([])
  const mimeTypeRef = useRef<string>('audio/webm')

  const isSupported =
    typeof navigator !== 'undefined' &&
    Boolean(navigator.mediaDevices?.getUserMedia) &&
    typeof MediaRecorder !== 'undefined'

  /** Stops all mic tracks so the browser's recording indicator turns off. */
  const releaseStream = useCallback(() => {
    streamRef.current?.getTracks().forEach((track) => track.stop())
    streamRef.current = null
  }, [])

  useEffect(() => releaseStream, [releaseStream])

  const startRecording = useCallback(async (): Promise<string | null> => {
    setError(null)
    setErrorType(null)

    if (!isSupported) {
      const message = 'Voice recording is not supported in this browser.'
      setError(message)
      setErrorType('unsupported')
      return message
    }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
      streamRef.current = stream

      const mimeType = pickSupportedMimeType()
      mimeTypeRef.current = mimeType ?? 'audio/webm'
      const recorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream)

      chunksRef.current = []
      recorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          chunksRef.current.push(event.data)
        }
      }
      recorder.onerror = () => {
        setError('Recording failed unexpectedly. Please try again.')
        setErrorType('unknown')
        releaseStream()
        setIsRecording(false)
      }

      mediaRecorderRef.current = recorder
      recorder.start()
      setIsRecording(true)
      return null
    } catch (err) {
      releaseStream()
      setIsRecording(false)

      const name = err instanceof DOMException ? err.name : ''
      let message: string
      let type: RecorderErrorType

      if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
        message = 'Microphone access was denied. Please allow microphone permission and try again.'
        type = 'permission-denied'
      } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
        message = 'No microphone was found. Please connect a microphone and try again.'
        type = 'no-microphone'
      } else {
        message = 'Could not start recording. Please try again.'
        type = 'unknown'
      }

      setError(message)
      setErrorType(type)
      return message
    }
  }, [isSupported, releaseStream])

  const stopRecording = useCallback((): Promise<Blob | null> => {
    const recorder = mediaRecorderRef.current

    if (!recorder || recorder.state === 'inactive') {
      setIsRecording(false)
      releaseStream()
      chunksRef.current = []
      return Promise.resolve(null)
    }

    return new Promise((resolve) => {
      recorder.onstop = () => {
        releaseStream()
        mediaRecorderRef.current = null
        setIsRecording(false)

        if (chunksRef.current.length === 0) {
          chunksRef.current = []
          resolve(null)
          return
        }

        const recording = new Blob(chunksRef.current, { type: mimeTypeRef.current })
        chunksRef.current = []
        resolve(recording)
      }

      recorder.stop()
    })
  }, [releaseStream])

  return { isRecording, isSupported, error, errorType, startRecording, stopRecording }
}

