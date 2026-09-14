import { useCallback, useEffect, useState } from 'react';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';
import UserTable from '../components/UserTable';

export default function Users() {
    const { user: currentUser } = useAuth();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [changingId, setChangingId] = useState(null);

    const loadUsers = useCallback(async () => {
        setLoading(true);

        try {
            const { data } = await api.get('/admin/users');
            setUsers(data.data ?? []);
            setError('');
        } catch {
            setError('Failed to load users.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadUsers();
    }, [loadUsers]);

    const mutate = async (url, payload) => {
        setError('');
        await api.patch(url, payload);
        await loadUsers();
    };

    const handleRoleChange = async (user, role) => {
        setChangingId(user.id);

        try {
            await mutate(`/admin/users/${user.id}`, { role });
        } catch {
            setError('Failed to update role.');
        } finally {
            setChangingId(null);
        }
    };

    const handleToggleActive = async (user) => {
        if (user.is_active && !window.confirm(`Deactivate ${user.name}? They will no longer be able to sign in.`)) {
            return;
        }

        setChangingId(user.id);

        try {
            await mutate(`/admin/users/${user.id}`, { is_active: !user.is_active });
        } catch {
            setError('Failed to update user status.');
        } finally {
            setChangingId(null);
        }
    };

    return (
        <div>
            <p className="text-sm text-slate-500">Manage customer accounts, roles, and access.</p>

            {error && (
                <div className="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
            )}

            <div className="mt-6">
                {loading ? (
                    <div className="rounded-xl border border-slate-200 bg-white p-16 text-center text-sm text-slate-400">
                        Loading users…
                    </div>
                ) : (
                    <UserTable
                        users={users}
                        currentUserId={currentUser?.id}
                        canManageSuperAdmin={currentUser?.role === 'super_admin'}
                        onRoleChange={handleRoleChange}
                        onToggleActive={handleToggleActive}
                        changingId={changingId}
                    />
                )}
            </div>
        </div>
    );
}