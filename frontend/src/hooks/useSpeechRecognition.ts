import { useCallback, useEffect, useMemo, useRef, useState } from 'react'

export type SpeechRecognitionErrorType =
  | 'permission-denied'
  | 'no-microphone'
  | 'unsupported'
  | 'no-speech'
  | 'network'
  | 'unknown'

type SpeechRecognitionConstructor = new () => SpeechRecognitionInstance

interface SpeechRecognitionResultItem {
  transcript: string
}

interface SpeechRecognitionResult {
  isFinal: boolean
  [index: number]: SpeechRecognitionResultItem
}

interface SpeechRecognitionResultList {
  length: number
  [index: number]: SpeechRecognitionResult
}

interface SpeechRecognitionEvent extends Event {
  results: SpeechRecognitionResultList
  resultIndex: number
}

interface SpeechRecognitionErrorEvent extends Event {
  error: string
}

interface SpeechRecognitionInstance extends EventTarget {
  continuous: boolean
  interimResults: boolean
  lang: string
  onend: (() => void) | null
  onerror: ((event: SpeechRecognitionErrorEvent) => void) | null
  onresult: ((event: SpeechRecognitionEvent) => void) | null
  start: () => void
  stop: () => void
  abort: () => void
}

interface SpeechRecognitionWindow extends Window {
  SpeechRecognition?: SpeechRecognitionConstructor
  webkitSpeechRecognition?: SpeechRecognitionConstructor
}

interface UseSpeechRecognitionResult {
  isListening: boolean
  isSupported: boolean
  error: string | null
  errorType: SpeechRecognitionErrorType | null
  startListening: () => Promise<string>
  stopListening: () => void
}

function getRecognitionConstructor(): SpeechRecognitionConstructor | undefined {
  if (typeof window === 'undefined') {
    return undefined
  }

  const speechWindow = window as SpeechRecognitionWindow
  return speechWindow.SpeechRecognition ?? speechWindow.webkitSpeechRecognition
}

function errorMessageFor(error: string): { message: string; type: SpeechRecognitionErrorType } {
  if (error === 'not-allowed' || error === 'service-not-allowed') {
    return {
      message: 'Microphone access was denied. Please allow microphone permission and try again.',
      type: 'permission-denied',
    }
  }

  if (error === 'audio-capture') {
    return {
      message: 'No microphone was found. Please connect a microphone and try again.',
      type: 'no-microphone',
    }
  }

  if (error === 'no-speech') {
    return {
      message: 'I did not hear any speech. Please try again.',
      type: 'no-speech',
    }
  }

  if (error === 'network') {
    return {
      message: 'Speech recognition could not connect. Please check your network and try again.',
      type: 'network',
    }
  }

  return {
    message: 'Speech recognition failed. Please try again.',
    type: 'unknown',
  }
}

export function useSpeechRecognition(): UseSpeechRecognitionResult {
  const [isListening, setIsListening] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [errorType, setErrorType] = useState<SpeechRecognitionErrorType | null>(null)
  const recognitionRef = useRef<SpeechRecognitionInstance | null>(null)
  const resolveRef = useRef<((text: string) => void) | null>(null)
  const rejectRef = useRef<((error: Error) => void) | null>(null)
  const finalTranscriptRef = useRef('')
  const interimTranscriptRef = useRef('')

  const Recognition = useMemo(() => getRecognitionConstructor(), [])
  const isSupported = Boolean(Recognition)

  const cleanup = useCallback(() => {
    recognitionRef.current = null
    resolveRef.current = null
    rejectRef.current = null
    finalTranscriptRef.current = ''
    interimTranscriptRef.current = ''
    setIsListening(false)
  }, [])

  useEffect(() => {
    return () => {
      recognitionRef.current?.abort()
      cleanup()
    }
  }, [cleanup])

  const stopListening = useCallback(() => {
    recognitionRef.current?.stop()
  }, [])

  const startListening = useCallback((): Promise<string> => {
    setError(null)
    setErrorType(null)

    if (!Recognition) {
      const message = 'Speech recognition is not supported in this browser. Please use Chrome or Edge.'
      setError(message)
      setErrorType('unsupported')
      return Promise.reject(new Error(message))
    }

    recognitionRef.current?.abort()

    return new Promise((resolve, reject) => {
      const recognition = new Recognition()
      recognition.continuous = false
      recognition.interimResults = true
      recognition.lang = 'en-US'

      recognitionRef.current = recognition
      resolveRef.current = resolve
      rejectRef.current = reject
      finalTranscriptRef.current = ''
      interimTranscriptRef.current = ''

      recognition.onresult = (event) => {
        let interimTranscript = ''

        for (let index = event.resultIndex; index < event.results.length; index += 1) {
          const transcript = event.results[index][0]?.transcript ?? ''

          if (event.results[index].isFinal) {
            finalTranscriptRef.current = `${finalTranscriptRef.current} ${transcript}`.trim()
          } else {
            interimTranscript += transcript
          }
        }

        interimTranscriptRef.current = interimTranscript.trim()
      }

      recognition.onerror = (event) => {
        const { message, type } = errorMessageFor(event.error)
        setError(message)
        setErrorType(type)
        reject(new Error(message))
        cleanup()
      }

      recognition.onend = () => {
        const transcript = `${finalTranscriptRef.current} ${interimTranscriptRef.current}`.trim()

        if (!transcript) {
          const message = 'I did not hear any speech. Please try again.'
          setError(message)
          setErrorType('no-speech')
          rejectRef.current?.(new Error(message))
          cleanup()
          return
        }

        resolveRef.current?.(transcript)
        cleanup()
      }

      try {
        recognition.start()
        setIsListening(true)
      } catch {
        const message = 'Speech recognition could not start. Please try again.'
        setError(message)
        setErrorType('unknown')
        reject(new Error(message))
        cleanup()
      }
    })
  }, [Recognition, cleanup])

  return { isListening, isSupported, error, errorType, startListening, stopListening }
}
