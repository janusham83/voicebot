import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export default function Dashboard() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  async function handleLogout() {
    await logout()
    navigate('/login')
  }

  return (
    <div className="container py-5">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h1 className="h3 mb-0">Welcome, {user?.name}</h1>
        <button type="button" className="btn btn-outline-secondary" onClick={handleLogout}>
          Logout
        </button>
      </div>

      <div className="card">
        <div className="card-body text-center py-5">
          <i className="bi bi-mic-fill display-4 text-primary" />
          <h2 className="h5 mt-3">Ready to talk to your AI VoiceBot?</h2>
          <Link to="/voicebot" className="btn btn-primary mt-3">
            Open VoiceBot
          </Link>
          {user?.is_admin && (
            <Link to="/admin" className="btn btn-outline-primary mt-3 ms-2">
              Admin Dashboard
            </Link>
          )}
        </div>
      </div>
    </div>
  )
}
