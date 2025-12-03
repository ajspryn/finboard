// resources/js/App.tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import { DashboardProvider } from '@/contexts/DashboardContext';
import Dashboard from '@/components/Dashboard';

function App() {
  return (
    <DashboardProvider>
      <Dashboard />
    </DashboardProvider>
  );
}

const container = document.getElementById('app');
if (container) {
  const root = createRoot(container);
  root.render(<App />);
}

export default App;
