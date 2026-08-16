import api from './api'
import type {
  ApiResponse,
  ChatResult,
  Conversation,
  Language,
  VoiceMessage,
  VoiceSettings,
  AdminDashboardData,
} from '../types/voice'

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

  async chat(message: string, conversationId?: number, language: Language = 'en') {
    const { data } = await api.post<ApiResponse<ChatResult>>('/voice/chat', {
      message,
      conversation_id: conversationId,
      language,
    })
    return data.data
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
