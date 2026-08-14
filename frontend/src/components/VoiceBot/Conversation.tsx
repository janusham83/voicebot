import { useEffect, useRef } from 'react'
import type { VoiceMessage } from '../../types/voice'
import MessageBubble from './MessageBubble'

export default function Conversation({ messages }: { messages: VoiceMessage[] }) {
  const bottomRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages.length])

  if (messages.length === 0) {
    return (
      <div className="conversation conversation--empty">
        <p className="text-muted text-center mt-5">Say something to start the conversation.</p>
      </div>
    )
  }

  return (
    <div className="conversation">
      {messages.map((message) => (
        <MessageBubble key={message.id} message={message} />
      ))}
      <div ref={bottomRef} />
    </div>
  )
}
