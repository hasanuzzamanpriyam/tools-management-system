import { useRef, useState } from 'react';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { useAvatarUrl } from '../hooks/useAvatarUrl';

function initialsFor(name) {
    return (name ?? '?')
        .split(' ')
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function extractErrors(error) {
    return (
        error.response?.data?.errors ?? {
            form: [error.response?.data?.message || 'Something went wrong. Please try again.'],
        }
    );
}

const inputClasses =
    'mt-1 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';

export default function Settings() {
    const { user, updateUser } = useAuth();
    const avatarUrl = useAvatarUrl(user);
    const fileInputRef = useRef(null);

    const [name, setName] = useState(user?.name ?? '');
    const [email, setEmail] = useState(user?.email ?? '');
    const [profileErrors, setProfileErrors] = useState({});
    const [profileStatus, setProfileStatus] = useState('');
    const [savingProfile, setSavingProfile] = useState(false);
    const [savingAvatar, setSavingAvatar] = useState(false);

    const [passwords, setPasswords] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [passwordErrors, setPasswordErrors] = useState({});
    const [passwordStatus, setPasswordStatus] = useState('');
    const [savingPassword, setSavingPassword] = useState(false);

    const handleProfileSubmit = async (event) => {
        event.preventDefault();
        setProfileErrors({});
        setProfileStatus('');
        setSavingProfile(true);

        try {
            const { data } = await api.patch('/me', { name, email });
            updateUser(data);
            setProfileStatus('Profile updated successfully.');
        } catch (error) {
            setProfileErrors(extractErrors(error));
        } finally {
            setSavingProfile(false);
        }
    };

    const handleAvatarChange = async (event) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        setProfileErrors({});
        setProfileStatus('');
        setSavingAvatar(true);

        const payload = new FormData();
        payload.append('avatar', file);

        try {
            const { data } = await api.post('/me/avatar', payload);
            updateUser(data);
            setProfileStatus('Profile picture updated.');
        } catch (error) {
            setProfileErrors(extractErrors(error));
        } finally {
            setSavingAvatar(false);

            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const handleAvatarRemove = async () => {
        setProfileErrors({});
        setProfileStatus('');
        setSavingAvatar(true);

        try {
            const { data } = await api.delete('/me/avatar');
            updateUser(data);
            setProfileStatus('Profile picture removed.');
        } catch (error) {
            setProfileErrors(extractErrors(error));
        } finally {
            setSavingAvatar(false);
        }
    };

    const handlePasswordSubmit = async (event) => {
        event.preventDefault();
        setPasswordErrors({});
        setPasswordStatus('');
        setSavingPassword(true);

        try {
            await api.put('/me/password', passwords);
            setPasswords({ current_password: '', password: '', password_confirmation: '' });
            setPasswordStatus('Password changed successfully. Other sessions were signed out.');
        } catch (error) {
            setPasswordErrors(extractErrors(error));
        } finally {
            setSavingPassword(false);
        }
    };

    return (
        <div className="max-w-3xl space-y-8">
            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-base font-semibold text-slate-900">Profile</h2>
                <p className="mt-1 text-sm text-slate-500">Update your account details and profile picture.</p>

                {profileStatus && (
                    <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {profileStatus}
                    </div>
                )}

                {profileErrors.form && (
                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {profileErrors.form[0]}
                    </div>
                )}

                <div className="mt-6 flex items-center gap-5">
                    {avatarUrl ? (
                        <img
                            src={avatarUrl}
                            alt="Profile picture"
                            className="h-16 w-16 rounded-full object-cover ring-2 ring-white shadow"
                        />
                    ) : (
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-lg font-semibold text-indigo-700">
                            {initialsFor(user?.name)}
                        </div>
                    )}

                    <div className="flex flex-wrap items-center gap-3">
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            className="hidden"
                            onChange={handleAvatarChange}
                        />
                        <button
                            type="button"
                            onClick={() => fileInputRef.current?.click()}
                            disabled={savingAvatar}
                            className="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {savingAvatar ? 'Uploading…' : 'Upload picture'}
                        </button>
                        {user?.avatar_path && (
                            <button
                                type="button"
                                onClick={handleAvatarRemove}
                                disabled={savingAvatar}
                                className="rounded-lg px-3.5 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Remove
                            </button>
                        )}
                        <p className="w-full text-xs text-slate-400">PNG, JPG or WebP up to 2MB.</p>
                    </div>
                </div>

                {profileErrors.avatar && <p className="mt-2 text-xs text-red-600">{profileErrors.avatar[0]}</p>}

                <form className="mt-6 space-y-5" onSubmit={handleProfileSubmit}>
                    <div>
                        <label htmlFor="name" className="block text-sm font-medium text-slate-700">
                            Full name
                        </label>
                        <input
                            id="name"
                            type="text"
                            required
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            className={inputClasses}
                        />
                        {profileErrors.name && <p className="mt-1 text-xs text-red-600">{profileErrors.name[0]}</p>}
                    </div>

                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-slate-700">
                            Email address
                        </label>
                        <input
                            id="email"
                            type="email"
                            required
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            className={inputClasses}
                        />
                        {profileErrors.email && <p className="mt-1 text-xs text-red-600">{profileErrors.email[0]}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={savingProfile}
                        className="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {savingProfile ? 'Saving…' : 'Save changes'}
                    </button>
                </form>
            </section>

            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-base font-semibold text-slate-900">Security</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Change your password. Signing out other devices keeps your account secure.
                </p>

                {passwordStatus && (
                    <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {passwordStatus}
                    </div>
                )}

                {passwordErrors.form && (
                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {passwordErrors.form[0]}
                    </div>
                )}

                <form className="mt-6 space-y-5" onSubmit={handlePasswordSubmit}>
                    <div>
                        <label htmlFor="current_password" className="block text-sm font-medium text-slate-700">
                            Current password
                        </label>
                        <input
                            id="current_password"
                            type="password"
                            required
                            autoComplete="current-password"
                            value={passwords.current_password}
                            onChange={(e) => setPasswords({ ...passwords, current_password: e.target.value })}
                            className={inputClasses}
                        />
                        {passwordErrors.current_password && (
                            <p className="mt-1 text-xs text-red-600">{passwordErrors.current_password[0]}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="new_password" className="block text-sm font-medium text-slate-700">
                            New password
                        </label>
                        <input
                            id="new_password"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={passwords.password}
                            onChange={(e) => setPasswords({ ...passwords, password: e.target.value })}
                            className={inputClasses}
                        />
                        {passwordErrors.password && (
                            <p className="mt-1 text-xs text-red-600">{passwordErrors.password[0]}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-slate-700">
                            Confirm new password
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={passwords.password_confirmation}
                            onChange={(e) => setPasswords({ ...passwords, password_confirmation: e.target.value })}
                            className={inputClasses}
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={savingPassword}
                        className="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {savingPassword ? 'Updating…' : 'Update password'}
                    </button>
                </form>
            </section>
        </div>
    );
}