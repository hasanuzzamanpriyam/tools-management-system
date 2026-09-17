import { useEffect, useState } from 'react';
import { api } from '../api/client';

export function useAvatarUrl(user) {
    const [url, setUrl] = useState(null);

    useEffect(() => {
        if (!user?.avatar_url) {
            setUrl(null);

            return;
        }

        let objectUrl;
        let cancelled = false;

        api.get('/me/avatar', { responseType: 'blob' })
            .then(({ data }) => {
                if (cancelled) {
                    return;
                }

                objectUrl = URL.createObjectURL(data);
                setUrl(objectUrl);
            })
            .catch(() => {
                if (!cancelled) {
                    setUrl(null);
                }
            });

        return () => {
            cancelled = true;

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        };
    }, [user?.avatar_url]);

    return url;
}