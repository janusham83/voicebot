import { useEffect, useRef, useState } from 'react'
import type { Language, MicState, VoiceMessage } from '../../types/voice'
import { useVoiceRecorder } from '../../hooks/useVoiceRecorder'
import { getApiErrorMessage } from '../../services/api'
import { voiceService } from '../../services/voiceService'
import Conversation from './Conversation'
import MicrophoneButton from './MicrophoneButton'
import VoiceStatus from './VoiceStatus'
import AudioPlayer from './AudioPlayer'

const DEFAULT_LANGUAGE: Language = 'auto'
const DEFAULT_VOICE = 'alloy'

export default function VoiceBot() {
  const [micState, setMicState] = useState<MicState>('idle')
  const [messages, setMessages] = useState<VoiceMessage[]>([])
  const [conversationId, setConversationId] = useState<number>()
  const [language, setLanguage] = useState<Language>(DEFAULT_LANGUAGE)
  const [voice, setVoice] = useState(DEFAULT_VOICE)
  const [autoPlay, setAutoPlay] = useState(true)
  const [audioUrl, setAudioUrl] = useState<string | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const audioRef = useRef<HTMLAudioElement>(null)
  const { isRecording, isSupported, startRecording, stopRecording } = useVoiceRecorder()

  useEffect(() => {
    let ignore = false

    async function loadInitialConversation() {
      try {
        const [settings, conversations] = await Promise.all([
          voiceService.getSettings().catch(() => null),
          voiceService.getConversations(),
        ])

        if (ignore) {
          return
        }

        if (settings) {
          setLanguage(settings.language)
          setVoice(settings.voice)
          setAutoPlay(settings.auto_play)
        }

        const latestConversation = conversations[0]
        if (latestConversation) {
          setConversationId(latestConversation.id)
          setLanguage((latestConversation.language || settings?.language || DEFAULT_LANGUAGE))
          setMessages(await voiceService.getMessages(latestConversation.id))
        }
      } catch (error) {
        if (!ignore) {
          setErrorMessage(getApiErrorMessage(error, 'Could not load conversation history.'))
          setMicState('error')
        }
      }
    }

    void loadInitialConversation()

    return () => {
      ignore = true
    }
  }, [])

  useEffect(() => {
    if (!audioUrl || !autoPlay) {
      return
    }

    void audioRef.current?.play().then(() => setMicState('speaking')).catch(() => setMicState('idle'))
  }, [audioUrl, autoPlay])

  async function updateLanguage(nextLanguage: Language) {
    setLanguage(nextLanguage)
    try {
      await voiceService.updateSettings({ language: nextLanguage })
    } catch {
      // Language can still be sent with each request if saving preferences fails.
    }
  }

  async function handleMicClick() {
    if (micState === 'error') {
      setMicState('idle')
      setErrorMessage(null)
      return
    }

    if (micState === 'speaking') {
      audioRef.current?.pause()
      setAudioUrl(null)
      setMicState('idle')
      return
    }

    if (!isSupported) {
      setErrorMessage('Voice recording is not supported in this browser.')
      setMicState('error')
      return
    }

    if (isRecording) {
      setMicState('processing')
      const audioBlob = await stopRecording()

      if (!audioBlob) {
        setErrorMessage('No audio was captured. Please try again.')
        setMicState('error')
        return
      }

      try {
        const transcript = await voiceService.transcribe(audioBlob, language)
        const chat = await voiceService.chat(transcript.text, conversationId, language)

        setConversationId(chat.conversation_id)
        setMessages((currentMessages) => [
          ...currentMessages,
          chat.user_message,
          chat.assistant_message,
        ])

        const speech = await voiceService.synthesize(chat.message, voice, chat.assistant_message.id)
        setAudioUrl(speech.audio_url)
        setMicState(autoPlay ? 'speaking' : 'idle')
      } catch (error) {
        setErrorMessage(getApiErrorMessage(error, 'Voice request failed. Please try again.'))
        setMicState('error')
      }
      return
    }

    setMicState('listening')
    const startError = await startRecording()

    if (startError) {
      setErrorMessage(startError)
      setMicState('error')
    }
  }

  return (
    <div className="voicebot">
      <div className="voicebot__language" aria-label="Language">
        {[
          ['auto', 'Auto'],
          ['si', 'Sinhala'],
          ['en', 'English'],
        ].map(([value, label]) => (
          <button
            key={value}
            type="button"
            className={`voicebot__language-option ${language === value ? 'is-active' : ''}`}
            onClick={() => updateLanguage(value as Language)}
          >
            {label}
          </button>
        ))}
      </div>

      <Conversation messages={messages} />

      <div className="voicebot__controls">
        <MicrophoneButton state={micState} onClick={handleMicClick} />
        <VoiceStatus state={micState} message={micState === 'error' ? errorMessage : null} />
      </div>

      <AudioPlayer ref={audioRef} src={audioUrl} onEnded={() => setAudioUrl(null)} />
    </div>
  )
}
