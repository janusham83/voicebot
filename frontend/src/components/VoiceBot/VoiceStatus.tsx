import type { MicState } from '../../types/voice'

const STATE_LABELS: Record<MicState, string> = {
  idle: 'Idle',
  listening: 'Listening...',
  processing: 'Processing...',
  speaking: 'Speaking...',
  error: 'Error',
}

export default function VoiceStatus({ state, message }: { state: MicState; message?: string | null }) {
  return <p className={`voice-status voice-status--${state}`}>{message || STATE_LABELS[state]}</p>
}
