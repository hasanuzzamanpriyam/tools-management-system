import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import Login from './pages/Login';
import Register from './pages/Register';
import Placeholder from './pages/Placeholder';
import Dashboard from './pages/Dashboard';
import Tools from './pages/Tools';
import ToolDetail from './pages/ToolDetail';
import Users from './pages/Users';
import Store from './pages/Store';

export default function App() {
    return (
        <BrowserRouter>
            <AuthProvider>
                <Routes>
                    <Route path="/login" element={<Login />} />
                    <Route path="/register" element={<Register />} />
                    <Route path="/store" element={<Store />} />

                    <Route element={<ProtectedRoute />}>
                        <Route element={<Layout />}>
                            <Route path="/dashboard" element={<Dashboard />} />
                            <Route path="/tools" element={<Tools />} />
                            <Route path="/tools/:id" element={<ToolDetail />} />
                            <Route path="/users" element={<Users />} />
                            <Route
                                path="/revenue"
                                element={<Placeholder title="Revenue" description="Track subscriptions and earnings." />}
                            />
                            <Route
                                path="/settings"
                                element={<Placeholder title="Settings" description="Configure your store." />}
                            />

                            <Route path="/" element={<Navigate to="/dashboard" replace />} />
                        </Route>
                    </Route>

                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </AuthProvider>
        </BrowserRouter>
    );
}