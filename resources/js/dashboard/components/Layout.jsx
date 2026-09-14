import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const navigation = [
    {
        name: 'Dashboard',
        to: '/dashboard',
        icon: (
            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} aria-hidden="true">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"
                />
            </svg>
        ),
    },
    {
        name: 'Tools',
        to: '/tools',
        icon: (
            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} aria-hidden="true">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"
                />
            </svg>
        ),
    },
    {
        name: 'Users',
        to: '/users',
        icon: (
            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} aria-hidden="true">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                />
            </svg>
        ),
    },
    {
        name: 'Revenue',
        to: '/revenue',
        icon: (
            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} aria-hidden="true">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"
                />
            </svg>
        ),
    },
    {
        name: 'Settings',
        to: '/settings',
        icon: (
            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} aria-hidden="true">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"
                />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        ),
    },
];

const titles = Object.fromEntries(navigation.map((item) => [item.to, item.name]));

export default function Layout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const title = titles[location.pathname] ?? 'Dashboard';

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    const initials = (user?.name ?? '?')
        .split(' ')
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <div className="min-h-screen bg-slate-100">
            <aside className="fixed inset-y-0 left-0 z-20 flex w-60 flex-col bg-slate-900">
                <div className="flex items-center gap-3 border-b border-slate-800 px-5 py-5">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white">
                        T
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-white">Tools Management</p>
                        <p className="text-xs text-slate-400">Admin Panel</p>
                    </div>
                </div>

                <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    {navigation.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            className={({ isActive }) =>
                                `flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                                    isActive
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                                }`
                            }
                        >
                            {item.icon}
                            {item.name}
                        </NavLink>
                    ))}
                </nav>

                <div className="border-t border-slate-800 px-5 py-4">
                    <p className="text-xs text-slate-500">v0.1.0</p>
                </div>
            </aside>

            <div className="pl-60">
                <header className="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white/80 px-8 py-4 backdrop-blur">
                    <div>
                        <h1 className="text-lg font-semibold text-slate-900">{title}</h1>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                {initials}
                            </div>
                            <div className="leading-tight">
                                <p className="text-sm font-medium text-slate-900">{user?.name}</p>
                                <p className="text-xs text-slate-500">{user?.email}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={handleLogout}
                            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Logout
                        </button>
                    </div>
                </header>

                <main className="px-8 py-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}