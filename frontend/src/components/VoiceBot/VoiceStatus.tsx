import type { MicState } from '../../types/voice'

const STATE_LABELS: Record<MicState, string> = {
  idle: 'Click to speak',
  listening: 'Listening...',
  processing: 'Thinking...',
  speaking: 'Speaking...',
  error: 'Something went wrong',
}

export default function VoiceStatus({ state, message }: { state: MicState; message?: string | null }) {
  return <p className={`voice-status voice-status--${state}`}>{message || STATE_LABELS[state]}</p>
}
