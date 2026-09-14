import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Register() {
    const { register, isAuthenticated } = useAuth();
    const navigate = useNavigate();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (isAuthenticated) {
            navigate('/dashboard', { replace: true });
        }
    }, [isAuthenticated, navigate]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setLoading(true);

        try {
            await register({
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            navigate('/dashboard');
        } catch (err) {
            const response = err.response?.data;

            if (response?.errors) {
                setErrors(response.errors);
            } else {
                setErrors({ form: [response?.message || 'Unable to create your account. Please try again.'] });
            }
        } finally {
            setLoading(false);
        }
    };

    const inputClasses =
        'mt-1 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 px-4">
            <div className="pointer-events-none absolute -top-32 -right-32 h-96 w-96 rounded-full bg-indigo-600/20 blur-3xl"></div>
            <div className="pointer-events-none absolute -bottom-40 -left-32 h-96 w-96 rounded-full bg-purple-600/20 blur-3xl"></div>

            <div className="relative w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl sm:p-10">
                <div className="flex items-center justify-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-xl font-bold text-white">
                        T
                    </div>
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Create your account</h1>
                        <p className="text-sm text-slate-500">Start managing your digital tools</p>
                    </div>
                </div>

                {errors.form && (
                    <div className="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {errors.form[0]}
                    </div>
                )}

                <form className="mt-6 space-y-5" onSubmit={handleSubmit}>
                    <div>
                        <label htmlFor="name" className="block text-sm font-medium text-slate-700">
                            Full name
                        </label>
                        <input
                            id="name"
                            type="text"
                            required
                            autoComplete="name"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            className={inputClasses}
                            placeholder="Jane Smith"
                        />
                        {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name[0]}</p>}
                    </div>

                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-slate-700">
                            Email address
                        </label>
                        <input
                            id="email"
                            type="email"
                            required
                            autoComplete="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            className={inputClasses}
                            placeholder="you@example.com"
                        />
                        {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email[0]}</p>}
                    </div>

                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-slate-700">
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className={inputClasses}
                            placeholder="••••••••"
                        />
                        {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password[0]}</p>}
                    </div>

                    <div>
                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-slate-700">
                            Confirm password
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={passwordConfirmation}
                            onChange={(e) => setPasswordConfirmation(e.target.value)}
                            className={inputClasses}
                            placeholder="••••••••"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {loading ? 'Creating account…' : 'Create account'}
                    </button>
                </form>

                <p className="mt-8 text-center text-sm text-slate-500">
                    Already have an account?{' '}
                    <Link to="/login" className="font-semibold text-indigo-600 hover:text-indigo-500">
                        Sign in
                    </Link>
                </p>
            </div>
        </div>
    );
}