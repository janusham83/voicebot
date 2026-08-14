import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getApiErrorMessage } from '../services/api'
import { voiceService } from '../services/voiceService'
import type { AdminDashboardData } from '../types/voice'

export default function AdminDashboard() {
  const [data, setData] = useState<AdminDashboardData | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    voiceService
      .getAdminDashboard()
      .then(setData)
      .catch((requestError) => setError(getApiErrorMessage(requestError, 'Could not load admin dashboard.')))
  }, [])

  return (
    <div className="container py-5">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h1 className="h3 mb-0">Admin Dashboard</h1>
        <Link to="/dashboard" className="btn btn-outline-secondary">
          Back
        </Link>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {data && (
        <>
          <div className="row g-3 mb-4">
            {Object.entries(data.stats).map(([label, value]) => (
              <div className="col-6 col-md-3" key={label}>
                <div className="card">
                  <div className="card-body">
                    <div className="text-muted text-capitalize small">{label}</div>
                    <div className="h4 mb-0">{value}</div>
                  </div>
                </div>
              </div>
            ))}
          </div>

          <div className="card">
            <div className="card-header">Recent Conversations</div>
            <div className="table-responsive">
              <table className="table mb-0">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>User</th>
                    <th>Language</th>
                    <th>Messages</th>
                  </tr>
                </thead>
                <tbody>
                  {data.recent_conversations.map((conversation) => (
                    <tr key={conversation.id}>
                      <td>{conversation.title || 'Untitled'}</td>
                      <td>{conversation.user?.email || 'Unknown'}</td>
                      <td>{conversation.language}</td>
                      <td>{conversation.messages_count}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </>
      )}
    </div>
  )
}
