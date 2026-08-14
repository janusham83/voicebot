import { Link } from 'react-router-dom'
import VoiceBot from '../components/VoiceBot/VoiceBot'

export default function VoiceBotPage() {
  return (
    <div className="voicebot-page">
      <header className="voicebot-page__header">
        <Link to="/dashboard" className="btn btn-sm btn-outline-secondary">
          <i className="bi bi-arrow-left" /> Back
        </Link>
        <h1 className="h5 mb-0">AI VoiceBot</h1>
        <span />
      </header>

      <VoiceBot />
    </div>
  )
}
