const ROLE_OPTIONS = ['user', 'admin', 'super_admin'];

function initials(name) {
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function StatusBadge({ isActive }) {
    return (
        <span className={`inline-flex items-center gap-1.5 text-xs font-medium ${isActive ? 'text-green-700' : 'text-slate-500'}`}>
            <span className={`h-1.5 w-1.5 rounded-full ${isActive ? 'bg-green-500' : 'bg-slate-300'}`}></span>
            {isActive ? 'Active' : 'Inactive'}
        </span>
    );
}

export default function UserTable({ users, currentUserId, canManageSuperAdmin, onRoleChange, onToggleActive, changingId }) {
    if (users.length === 0) {
        return (
            <div className="rounded-xl border-2 border-dashed border-slate-200 bg-white p-16 text-center">
                <p className="text-sm font-medium text-slate-400">No users yet.</p>
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table className="min-w-full divide-y divide-slate-200">
                <thead className="bg-slate-50">
                    <tr>
                        <th className="px-5 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">User</th>
                        <th className="px-5 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Role</th>
                        <th className="px-5 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Status</th>
                        <th className="px-5 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Joined</th>
                        <th className="px-5 py-3 text-right text-xs font-semibold tracking-wide text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {users.map((user) => {
                        const isSelf = user.id === currentUserId;
                        const assignableRoles = isSelf ? [user.role] : canManageSuperAdmin ? ROLE_OPTIONS : ['user', 'admin'];
                        const busy = changingId === user.id;

                        return (
                            <tr key={user.id} className="transition hover:bg-slate-50">
                                <td className="px-5 py-3.5">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                            {initials(user.name)}
                                        </div>
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <p className="truncate text-sm font-medium text-slate-900">{user.name}</p>
                                                {isSelf && (
                                                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">You</span>
                                                )}
                                            </div>
                                            <p className="truncate text-xs text-slate-500">{user.email}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-5 py-3.5">
                                    {busy ? (
                                        <span className="text-xs text-slate-400">Saving…</span>
                                    ) : (
                                        <select
                                            value={user.role}
                                            disabled={isSelf}
                                            onChange={(e) => onRoleChange(user, e.target.value)}
                                            className="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                                        >
                                            {assignableRoles.map((role) => (
                                                <option key={role} value={role}>
                                                    {role.replace('_', ' ')}
                                                </option>
                                            ))}
                                        </select>
                                    )}
                                </td>
                                <td className="px-5 py-3.5"><StatusBadge isActive={user.is_active} /></td>
                                <td className="px-5 py-3.5 text-sm text-slate-600">{formatDate(user.created_at)}</td>
                                <td className="px-5 py-3.5 text-right">
                                    <button
                                        type="button"
                                        disabled={isSelf}
                                        onClick={() => onToggleActive(user)}
                                        className={`text-sm font-medium disabled:cursor-not-allowed disabled:opacity-40 ${
                                            user.is_active ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'
                                        }`}
                                    >
                                        {user.is_active ? 'Deactivate' : 'Activate'}
                                    </button>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}