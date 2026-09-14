import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import Login from './pages/Login';
import Register from './pages/Register';
import Placeholder from './pages/Placeholder';

export default function App() {
    return (
        <BrowserRouter>
            <AuthProvider>
                <Routes>
                    <Route path="/login" element={<Login />} />
                    <Route path="/register" element={<Register />} />

                    <Route element={<ProtectedRoute />}>
                        <Route element={<Layout />}>
                            <Route
                                path="/dashboard"
                                element={<Placeholder title="Dashboard" description="Revenue overview and key metrics." />}
                            />
                            <Route
                                path="/tools"
                                element={<Placeholder title="Tools" description="Create and manage your digital tools." />}
                            />
                            <Route
                                path="/users"
                                element={<Placeholder title="Users" description="Manage customers and their roles." />}
                            />
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