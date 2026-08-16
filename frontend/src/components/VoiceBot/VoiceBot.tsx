import { useEffect, useRef, useState } from 'react'
import type { Language, MicState, VoiceMessage } from '../../types/voice'
import { useSpeechRecognition } from '../../hooks/useSpeechRecognition'
import { getApiErrorMessage } from '../../services/api'
import { voiceService } from '../../services/voiceService'
import Conversation from './Conversation'
import MicrophoneButton from './MicrophoneButton'
import VoiceStatus from './VoiceStatus'

const DEFAULT_LANGUAGE: Language = 'en'

export default function VoiceBot() {
  const [micState, setMicState] = useState<MicState>('idle')
  const [messages, setMessages] = useState<VoiceMessage[]>([])
  const [conversationId, setConversationId] = useState<number>()
  const [language, setLanguage] = useState<Language>(DEFAULT_LANGUAGE)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const utteranceRef = useRef<SpeechSynthesisUtterance | null>(null)
  const { isListening, isSupported, startListening, stopListening } = useSpeechRecognition()

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
          setLanguage(DEFAULT_LANGUAGE)
        }

        const latestConversation = conversations[0]
        if (latestConversation) {
          setConversationId(latestConversation.id)
          setLanguage(DEFAULT_LANGUAGE)
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
    return () => {
      window.speechSynthesis?.cancel()
    }
  }, [])

  function speakResponse(text: string) {
    if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
      setMicState('idle')
      setErrorMessage('Speech synthesis is not supported in this browser.')
      return
    }

    window.speechSynthesis.cancel()

    const utterance = new SpeechSynthesisUtterance(text)
    utterance.lang = 'en-US'
    utterance.rate = 1
    utterance.pitch = 1
    utterance.onstart = () => setMicState('speaking')
    utterance.onend = () => {
      utteranceRef.current = null
      setMicState('idle')
    }
    utterance.onerror = () => {
      utteranceRef.current = null
      setErrorMessage('Could not speak the AI response.')
      setMicState('error')
    }

    utteranceRef.current = utterance
    window.speechSynthesis.speak(utterance)
  }

  async function handleMicClick() {
    if (micState === 'error') {
      setMicState('idle')
      setErrorMessage(null)
      return
    }

    if (micState === 'speaking') {
      window.speechSynthesis.cancel()
      utteranceRef.current = null
      setMicState('idle')
      return
    }

    if (!isSupported) {
      setErrorMessage('Voice recording is not supported in this browser.')
      setMicState('error')
      return
    }

    if (isListening) {
      stopListening()
      return
    }

    try {
      setMicState('listening')
      const text = (await startListening()).trim()

      if (!text) {
        setErrorMessage('I did not hear any speech. Please try again.')
        setMicState('error')
        return
      }

      setMicState('processing')
      const chat = await voiceService.chat(text, conversationId, language)

      setConversationId(chat.conversation_id)
      setMessages((currentMessages) => [
        ...currentMessages,
        chat.user_message,
        chat.assistant_message,
      ])

      speakResponse(chat.message)
    } catch (error) {
      setErrorMessage(getApiErrorMessage(error, 'Voice request failed. Please try again.'))
      setMicState('error')
    }
  }

  return (
    <div className="voicebot">
      <Conversation messages={messages} />

      <div className="voicebot__controls">
        <MicrophoneButton state={micState} onClick={handleMicClick} />
        <VoiceStatus state={micState} message={micState === 'error' ? errorMessage : null} />
      </div>
    </div>
  )
}
