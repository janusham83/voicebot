export type Language = 'si' | 'en' | 'auto'

export type MessageRole = 'user' | 'assistant' | 'system'

export type MicState = 'idle' | 'listening' | 'processing' | 'speaking' | 'error'

export interface User {
  id: number
  name: string
  email: string
  is_admin: boolean
  created_at: string
  updated_at: string
}

export interface VoiceMessage {
  id: number
  conversation_id: number
  role: MessageRole
  message: string
  audio_file: string | null
  duration: number | null
  tokens: number | null
  created_at: string
  updated_at: string
}

export interface Conversation {
  id: number
  title: string | null
  language: Language
  system_prompt: string | null
  messages_count?: number
  messages?: VoiceMessage[]
  created_at: string
  updated_at: string
}

export interface VoiceSettings {
  id: number
  language: Language
  voice: string
  ai_model: string
  temperature: number
  auto_play: boolean
  created_at: string
  updated_at: string
}

export interface ApiResponse<T> {
  success: boolean
  message: string
  data: T
}

export interface TranscribeResult {
  text: string
  language: Language | null
}

export interface ChatResult {
  conversation_id: number
  message: string
  user_message: VoiceMessage
  assistant_message: VoiceMessage
}

export interface SynthesizeResult {
  audio_url: string
}

export interface AdminDashboardData {
  stats: {
    users: number
    conversations: number
    messages: number
    tokens: number
  }
  recent_conversations: Array<{
    id: number
    title: string | null
    language: Language
    user: Pick<User, 'id' | 'name' | 'email'> | null
    messages_count: number
    updated_at: string
  }>
}
