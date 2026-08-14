import type { MicState } from '../../types/voice'

interface MicrophoneButtonProps {
  state: MicState
  onClick: () => void
}

const STATE_ICONS: Record<MicState, string> = {
  idle: 'bi-mic-fill',
  listening: 'bi-mic-fill',
  processing: 'bi-hourglass-split',
  speaking: 'bi-volume-up-fill',
  error: 'bi-exclamation-triangle-fill',
}

export default function MicrophoneButton({ state, onClick }: MicrophoneButtonProps) {
  const disabled = state === 'processing'

  return (
    <button
      type="button"
      className={`mic-button mic-button--${state}`}
      onClick={onClick}
      disabled={disabled}
      aria-label="Toggle microphone"
    >
      <i className={`bi ${STATE_ICONS[state]}`} />
    </button>
  )
}
