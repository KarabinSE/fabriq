import { format, formatDistance, isValid, parseISO } from 'date-fns'
import { sv } from 'date-fns/locale'
import filesize from 'filesize'

export function formatLocalTime(value, dateFormat = null, formatDistanceEnabled = false) {
    let date = null

    if (value instanceof Date) {
        date = value
    } else {
        date = parseISO(value)
        if (!isValid(date)) {
            return value
        }
    }

    if (formatDistanceEnabled) {
        return formatDistance(date, new Date(), { locale: sv, addSuffix: true })
    }

    if (dateFormat) {
        return format(date, dateFormat, { locale: sv })
    }

    return format(date, 'yyyy-MM-dd HH:mm')
}

export function formatFileSize(value, options = {}) {
    if (!value) {
        return '0 B'
    }

    const mergedOptions = {
        locale: 'sv',
        ...options,
    }

    return filesize(value, mergedOptions)
}

export function formatCurrency(value, options = {}) {
    if (!value && value !== 0) {
        return ''
    }

    const numberValue = parseFloat(value)
    if (isNaN(numberValue)) {
        return value
    }

    const mergedOptions = {
        currency: 'SEK',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        ...options,
    }

    return new Intl.NumberFormat('sv-SE', {
        style: 'currency',
        ...mergedOptions,
    }).format(numberValue)
}
