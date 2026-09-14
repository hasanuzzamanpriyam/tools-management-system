import { createContext, useContext, useEffect, useState } from 'react';
import { api } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [token, setToken] = useState(() => localStorage.getItem('token'));
    const [user, setUser] = useState(() => {
        try {
            return JSON.parse(localStorage.getItem('user'));
        } catch {
            return null;
        }
    });

    useEffect(() => {
        if (!token) {
            return;
        }

        api.get('/me')
            .then(({ data }) => {
                setUser(data);
                localStorage.setItem('user', JSON.stringify(data));
            })
            .catch(() => {
                logout();
            });
    }, [token]);

    const persist = (token, user) => {
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        setToken(token);
        setUser(user);
    };

    const login = async (credentials) => {
        const { data } = await api.post('/login', credentials);
        persist(data.token, data.user);
    };

    const register = async (payload) => {
        const { data } = await api.post('/register', payload);
        persist(data.token, data.user);
    };

    const logout = async () => {
        try {
            await api.post('/logout');
        } catch {
            // token may already be invalid; clear locally regardless
        }

        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setToken(null);
        setUser(null);
    };

    return (
        <AuthContext.Provider value={{ user, token, isAuthenticated: Boolean(token), login, register, logout }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}