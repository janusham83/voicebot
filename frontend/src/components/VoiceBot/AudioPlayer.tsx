import { forwardRef } from 'react'

interface AudioPlayerProps {
  src: string | null
  onEnded?: () => void
}

/**
 * Hidden <audio> element controlled imperatively by the parent via the forwarded ref.
 * Playback control (auto-play, pause/resume, interrupt) is wired up in a later phase.
 */
const AudioPlayer = forwardRef<HTMLAudioElement, AudioPlayerProps>(({ src, onEnded }, ref) => {
  if (!src) {
    return null
  }

  return <audio ref={ref} src={src} onEnded={onEnded} />
})

AudioPlayer.displayName = 'AudioPlayer'

export default AudioPlayer
