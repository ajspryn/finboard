// resources/js/components/RealTimeUpdates.tsx
import React, { useEffect, useState } from 'react';
import { useDashboard } from '@/contexts/DashboardContext';

interface NotificationItem {
  id: string;
  type: 'success' | 'warning' | 'danger' | 'info';
  title: string;
  message: string;
  timestamp: Date;
  read: boolean;
}

export default function RealTimeUpdates() {
  const { state } = useDashboard();
  const [notifications, setNotifications] = useState<NotificationItem[]>([]);
  const [showNotifications, setShowNotifications] = useState(false);
  const [lastUpdate, setLastUpdate] = useState<Date>(new Date());

  // Simulate real-time updates
  useEffect(() => {
    const interval = setInterval(() => {
      setLastUpdate(new Date());

      // Check for alerts based on current data
      checkForAlerts();
    }, 30000); // Check every 30 seconds

    return () => clearInterval(interval);
  }, [state.data]);

  const checkForAlerts = () => {
    if (!state.data) return;

    const newNotifications: NotificationItem[] = [];

    // NPF Alert
    if (state.data.npf.ratio > 5) {
      newNotifications.push({
        id: `npf-${Date.now()}`,
        type: 'danger',
        title: 'NPF Tinggi',
        message: `NPF saat ini ${state.data.npf.ratio.toFixed(2)}% - melebihi threshold 5%`,
        timestamp: new Date(),
        read: false,
      });
    }

    // CAR Alert
    if (state.financialHighlights?.car && state.financialHighlights.car < 12) {
      newNotifications.push({
        id: `car-${Date.now()}`,
        type: 'warning',
        title: 'CAR Rendah',
        message: `CAR saat ini ${state.financialHighlights.car.toFixed(2)}% - di bawah threshold 12%`,
        timestamp: new Date(),
        read: false,
      });
    }

    // ROA Alert
    if (state.financialHighlights?.roa && state.financialHighlights.roa < 1) {
      newNotifications.push({
        id: `roa-${Date.now()}`,
        type: 'warning',
        title: 'ROA Rendah',
        message: `ROA saat ini ${state.financialHighlights.roa.toFixed(2)}% - di bawah threshold 1%`,
        timestamp: new Date(),
        read: false,
      });
    }

    if (newNotifications.length > 0) {
      setNotifications(prev => [...newNotifications, ...prev].slice(0, 10)); // Keep only latest 10
    }
  };

  const unreadCount = notifications.filter(n => !n.read).length;

  const markAsRead = (id: string) => {
    setNotifications(prev => prev.map(n => (n.id === id ? { ...n, read: true } : n)));
  };

  const markAllAsRead = () => {
    setNotifications(prev => prev.map(n => ({ ...n, read: true })));
  };

  return (
    <div className="position-fixed top-0 end-0 p-3" style={{ zIndex: 1050 }}>
      {/* Last Update Indicator */}
      <div className="toast show mb-2" role="alert">
        <div className="toast-body d-flex align-items-center">
          <i className="ti ti-refresh text-success me-2"></i>
          <small className="text-muted">Last updated: {lastUpdate.toLocaleTimeString()}</small>
        </div>
      </div>

      {/* Notifications Bell */}
      <div className="dropdown">
        <button
          className="btn btn-outline-primary position-relative"
          type="button"
          onClick={() => setShowNotifications(!showNotifications)}
        >
          <i className="ti ti-bell"></i>
          {unreadCount > 0 && (
            <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              {unreadCount}
            </span>
          )}
        </button>

        {showNotifications && (
          <div
            className="dropdown-menu dropdown-menu-end show"
            style={{ minWidth: '300px', maxHeight: '400px', overflowY: 'auto' }}
          >
            <div className="dropdown-header d-flex justify-content-between align-items-center">
              <h6 className="mb-0">Notifications</h6>
              {unreadCount > 0 && (
                <button className="btn btn-sm btn-link p-0" onClick={markAllAsRead}>
                  Mark all read
                </button>
              )}
            </div>
            <div className="dropdown-divider"></div>

            {notifications.length === 0 ? (
              <div className="dropdown-item text-center text-muted">
                <i className="ti ti-bell-off me-2"></i>
                No notifications
              </div>
            ) : (
              notifications.map(notification => (
                <div
                  key={notification.id}
                  className={`dropdown-item ${!notification.read ? 'bg-light' : ''}`}
                  onClick={() => markAsRead(notification.id)}
                  style={{ cursor: 'pointer' }}
                >
                  <div className="d-flex align-items-start">
                    <div
                      className={`avatar avatar-xs bg-label-${notification.type} rounded-circle me-2 mt-1`}
                    >
                      <i className={`ti ti-info-circle ti-xs text-${notification.type}`}></i>
                    </div>
                    <div className="flex-grow-1">
                      <div className="d-flex justify-content-between align-items-start">
                        <h6 className="mb-1 text-truncate">{notification.title}</h6>
                        <small className="text-muted">
                          {notification.timestamp.toLocaleTimeString()}
                        </small>
                      </div>
                      <p className="mb-0 small text-muted">{notification.message}</p>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        )}
      </div>
    </div>
  );
}
