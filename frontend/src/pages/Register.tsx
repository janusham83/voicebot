import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { getApiErrorMessage } from '../services/api'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    setSubmitting(true)

    try {
      await register(name, email, password, passwordConfirmation)
      navigate('/dashboard')
    } catch (err) {
      setError(getApiErrorMessage(err, 'Registration failed. Please check your details.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="auth-page">
      <form className="auth-card" onSubmit={handleSubmit}>
        <h1 className="h4 mb-3 text-center">AI VoiceBot</h1>
        <p className="text-muted text-center mb-4">Create your account</p>

        {error && <div className="alert alert-danger">{error}</div>}

        <div className="mb-3">
          <label className="form-label" htmlFor="name">Name</label>
          <input
            id="name"
            type="text"
            className="form-control"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
        </div>

        <div className="mb-3">
          <label className="form-label" htmlFor="email">Email</label>
          <input
            id="email"
            type="email"
            className="form-control"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
        </div>

        <div className="mb-3">
          <label className="form-label" htmlFor="password">Password</label>
          <input
            id="password"
            type="password"
            className="form-control"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            minLength={8}
          />
        </div>

        <div className="mb-3">
          <label className="form-label" htmlFor="password_confirmation">Confirm Password</label>
          <input
            id="password_confirmation"
            type="password"
            className="form-control"
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            required
            minLength={8}
          />
        </div>

        <button type="submit" className="btn btn-primary w-100" disabled={submitting}>
          {submitting ? 'Creating account...' : 'Register'}
        </button>

        <p className="text-center mt-3 mb-0">
          Already have an account? <Link to="/login">Sign in</Link>
        </p>
      </form>
    </div>
  )
}
