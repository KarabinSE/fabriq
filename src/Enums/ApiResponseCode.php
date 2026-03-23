<?php

namespace Karabin\Fabriq\Enums;

enum ApiResponseCode: string
{
    case WrongArgs = 'GEN-FUBARGS';
    case NotFound = 'GEN-LIKETHEWIND';
    case InternalError = 'GEN-AAAGGH';
    case Unauthorized = 'GEN-MAYBGTFO';
    case Forbidden = 'GEN-GTFO';
    case InvalidMimeType = 'GEN-UMWUT';
    case Success = 'GEN-NOICE';
}
