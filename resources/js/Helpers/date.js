/**
 * Format a date using Intl.DateTimeFormat
 * @param {Date|string} date - The date to format
 * @param {Object} options - Intl.DateTimeFormat options
 * @param {string} [locale='en-US'] - The locale to use
 * @returns {string} Formatted date string
 */
export function formatDate(date, options = {}, locale = 'en-US') {
    if (!date) return '';

    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    };

    const dateObj = date instanceof Date ? date : new Date(date);

    // Handle invalid dates
    if (isNaN(dateObj.getTime())) {
        console.warn('Invalid date provided to formatDate:', date);
        return '';
    }

    return new Intl.DateTimeFormat(locale, {
        ...defaultOptions,
        ...options,
    }).format(dateObj);
}

/**
 * Format a date in a short format (e.g., "MM/DD/YYYY")
 */
export function formatShortDate(date) {
    return formatDate(date, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
}

/**
 * Format a date with time
 */
export function formatDateTime(date) {
    return formatDate(date, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Format a date as a relative time (e.g., "2 days ago")
 */
export function formatRelativeTime(date, locale = 'en') {
    const now = new Date();
    const dateObj = date instanceof Date ? date : new Date(date);
    const diffInSeconds = Math.floor((now - dateObj) / 1000);

    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60,
        second: 1,
    };

    for (const [unit, seconds] of Object.entries(intervals)) {
        const interval = Math.floor(diffInSeconds / seconds);
        if (interval >= 1) {
            return new Intl.RelativeTimeFormat(locale, {
                numeric: 'auto',
            }).format(-interval, unit);
        }
    }

    return 'just now';
}

/**
 * Format a date in ISO format (YYYY-MM-DD)
 */
export function formatISODate(date) {
    const d = date instanceof Date ? date : new Date(date);
    return d.toISOString().split('T')[0];
}

/**
 * Check if a date is today
 */
export function isToday(date) {
    const d = date instanceof Date ? date : new Date(date);
    const today = new Date();
    return (
        d.getDate() === today.getDate() &&
        d.getMonth() === today.getMonth() &&
        d.getFullYear() === today.getFullYear()
    );
}

/**
 * Format a date, showing relative time if it's recent
 */
export function smartFormatDate(date, locale = 'en') {
    const d = date instanceof Date ? date : new Date(date);
    const now = new Date();
    const diffInDays = Math.floor((now - d) / (1000 * 60 * 60 * 24));

    if (isToday(d)) {
        return formatRelativeTime(d, locale);
    } else if (diffInDays < 7) {
        // For dates within the last week, show the day name
        return d.toLocaleDateString(locale, { weekday: 'long' });
    } else if (d.getFullYear() === now.getFullYear()) {
        // For dates in the current year, show month and day
        return d.toLocaleDateString(locale, { month: 'short', day: 'numeric' });
    } else {
        // For older dates, show full date
        return formatDate(d);
    }
}

export default {
    formatDate,
    formatShortDate,
    formatDateTime,
    formatRelativeTime,
    formatISODate,
    isToday,
    smartFormatDate,
};
