import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';

const inputClasses =
    'mt-1 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';

export default function ResetPassword() {
    const { setSession } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const token = searchParams.get('token') ?? '';
    const email = searchParams.get('email') ?? '';

    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError('');
        setLoading(true);

        try {
            const { data } = await api.post('/reset-password', {
                email,
                token,
                password,
                password_confirmation: passwordConfirmation,
            });

            setSession(data.token, data.user);
            navigate('/dashboard', { replace: true });
        } catch (err) {
            const errors = err.response?.data?.errors;
            setError(
                errors?.password?.[0] ||
                    errors?.email?.[0] ||
                    errors?.token?.[0] ||
                    err.response?.data?.message ||
                    'Unable to reset your password. Please request a new link.'
            );
        } finally {
            setLoading(false);
        }
    };

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
                        <h1 className="text-xl font-semibold text-slate-900">Reset password</h1>
                        <p className="text-sm text-slate-500">Choose a new password</p>
                    </div>
                </div>

                {!token || !email ? (
                    <div className="mt-6 space-y-4">
                        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            This password reset link is invalid or has expired.
                        </div>
                        <Link
                            to="/forgot-password"
                            className="block w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                        >
                            Request a new link
                        </Link>
                    </div>
                ) : (
                    <>
                        {error && (
                            <div className="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {error}
                            </div>
                        )}

                        <form className="mt-6 space-y-5" onSubmit={handleSubmit}>
                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-slate-700">
                                    New password
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
                            </div>

                            <div>
                                <label
                                    htmlFor="password_confirmation"
                                    className="block text-sm font-medium text-slate-700"
                                >
                                    Confirm new password
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
                                {loading ? 'Resetting…' : 'Reset password'}
                            </button>
                        </form>
                    </>
                )}

                <p className="mt-8 text-center text-sm text-slate-500">
                    <Link to="/login" className="font-semibold text-indigo-600 hover:text-indigo-500">
                        Back to sign in
                    </Link>
                </p>
            </div>
        </div>
    );
}