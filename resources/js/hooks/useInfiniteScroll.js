import { useEffect, useCallback } from 'react';

const useInfiniteScroll = (callback, hasMore, loading) => {
    const handleScroll = useCallback(() => {
        if (loading || !hasMore) return;

        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;

        // Trigger when user is near the bottom (within 100px)
        if (scrollTop + windowHeight >= documentHeight - 100) {
            callback();
        }
    }, [callback, hasMore, loading]);

    useEffect(() => {
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, [handleScroll]);

    return null;
};

export default useInfiniteScroll;

