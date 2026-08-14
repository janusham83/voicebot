import api from './api'
import type {
  ApiResponse,
  ChatResult,
  Conversation,
  Language,
  SynthesizeResult,
  TranscribeResult,
  VoiceMessage,
  VoiceSettings,
  AdminDashboardData,
} from '../types/voice'

function absoluteApiAssetUrl(url: string) {
  if (/^https?:\/\//i.test(url)) {
    return url
  }

  const apiUrl = import.meta.env.VITE_API_URL
  const origin = apiUrl ? new URL(apiUrl).origin : window.location.origin

  return `${origin}${url.startsWith('/') ? url : `/${url}`}`
}

function recordingFilename(audioBlob: Blob) {
  if (audioBlob.type.includes('mp4')) {
    return 'recording.mp4'
  }

  if (audioBlob.type.includes('ogg')) {
    return 'recording.ogg'
  }

  return 'recording.webm'
}

export const voiceService = {
  async getConversations() {
    const { data } = await api.get<ApiResponse<{ conversations: Conversation[] }>>('/conversations')
    return data.data.conversations
  },

  async createConversation(payload: { title?: string; language?: Language; system_prompt?: string } = {}) {
    const { data } = await api.post<ApiResponse<{ conversation: Conversation }>>('/conversations', payload)
    return data.data.conversation
  },

  async getConversation(id: number) {
    const { data } = await api.get<ApiResponse<{ conversation: Conversation }>>(`/conversations/${id}`)
    return data.data.conversation
  },

  async deleteConversation(id: number) {
    await api.delete(`/conversations/${id}`)
  },

  async getMessages(conversationId: number) {
    const { data } = await api.get<ApiResponse<{ messages: VoiceMessage[] }>>(
      `/conversations/${conversationId}/messages`
    )
    return data.data.messages
  },

  async transcribe(audioBlob: Blob, language: Language = 'auto') {
    const formData = new FormData()
    formData.append('audio', audioBlob, recordingFilename(audioBlob))
    formData.append('language', language)

    const { data } = await api.post<ApiResponse<TranscribeResult>>('/voice/transcribe', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.data
  },

  async chat(message: string, conversationId?: number, language: Language = 'auto') {
    const { data } = await api.post<ApiResponse<ChatResult>>('/voice/chat', {
      message,
      conversation_id: conversationId,
      language,
    })
    return data.data
  },

  async synthesize(text: string, voice?: string, messageId?: number) {
    const { data } = await api.post<ApiResponse<SynthesizeResult>>('/voice/synthesize', {
      text,
      voice,
      message_id: messageId,
    })
    return {
      ...data.data,
      audio_url: absoluteApiAssetUrl(data.data.audio_url),
    }
  },

  async getSettings() {
    const { data } = await api.get<ApiResponse<{ settings: VoiceSettings }>>('/settings')
    return data.data.settings
  },

  async updateSettings(payload: Partial<Pick<VoiceSettings, 'language' | 'voice' | 'ai_model' | 'temperature' | 'auto_play'>>) {
    const { data } = await api.put<ApiResponse<{ settings: VoiceSettings }>>('/settings', payload)
    return data.data.settings
  },

  async getAdminDashboard() {
    const { data } = await api.get<ApiResponse<AdminDashboardData>>('/admin/dashboard')
    return data.data
  },
}
