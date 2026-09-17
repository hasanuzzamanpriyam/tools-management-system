import { useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function OAuthCallback() {
    const { setSession } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();

    useEffect(() => {
        const token = searchParams.get('token');
        const email = searchParams.get('email');

        if (!token) {
            navigate('/login?oauth_error=oauth_failed', { replace: true });
            return;
        }

        setSession(token, { name: email ?? 'User', email: email ?? '', avatar_url: null });
        navigate('/dashboard', { replace: true });
    }, [navigate, searchParams, setSession]);

    return null;
}