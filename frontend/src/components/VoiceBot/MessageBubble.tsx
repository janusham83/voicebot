import type { VoiceMessage } from '../../types/voice'

export default function MessageBubble({ message }: { message: VoiceMessage }) {
  const isUser = message.role === 'user'

  if (message.role === 'system') {
    return <div className="message-bubble message-bubble--system">{message.message}</div>
  }

  return (
    <div className={`message-row ${isUser ? 'message-row--user' : 'message-row--assistant'}`}>
      <div className={`message-bubble ${isUser ? 'message-bubble--user' : 'message-bubble--assistant'}`}>
        <span className="message-bubble__role">{isUser ? 'You' : 'AI'}</span>
        <p className="message-bubble__text">{message.message}</p>
      </div>
    </div>
  )
}
