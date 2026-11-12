import { format, formatDistance, isValid, parseISO } from 'date-fns'
import { sv } from 'date-fns/locale'
import Vue from 'vue'

Vue.filter('localTime', function (value, dateFormat = null, formatDistanceEnabled = false) {
    let date = null
    if (value instanceof Date) {
        date = value
    } else {
        date = parseISO(value)
        if (!isValid(date)) {
            console.warn('Not a valid date: ', value)
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
})
