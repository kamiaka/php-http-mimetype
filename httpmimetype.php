<?php

class HTTPMimeType {

    /**
     * Negotiate MimeType for `Content-Type` HTTP header
     *
     * @param array        $supportedTypes
     * @param striing|null $defaultType
     * @return string|null matched supported type or default type
     */
    public static function negotiateContentType(array $supportedTypes, $defaultType = null) {
        return static::negotiateContentTypeString($_SERVER['CONTENT_TYPE'], $supportedTypes, $defaultType);
    }

    /**
     * Negotiate MimeType for `Content-Type` HTTP header string
     *
     * @param string       $contentType
     * @param array        $supportedTypes
     * @param striing|null $defaultType
     * @return string|null matched supported type or default type
     */
    public static function negotiateContentTypeString($contentType, array $supportedTypes, $defaultType = null) {
        if (empty($supportedTypes)) {
            return $defaultType;
        }
        $media = static::parseMediaType($contentType);
        $type = $media['type'];
        $subtype = $media['subtype'];
        $params = $media['parameters'];

        foreach ($supportedTypes as $supportedType) {
            $support = static::parseMediaType($supportedType);

            if ($type !== $support['type'] || $subtype !== $support['subtype']) {
                continue;
            }

            $bool = true;
            foreach ($support['parameters'] as $key => $val) {
                
                if ((!isset($params[$key])) || $params[$key] !== $val) {
                    $bool = false;
                    break;
                }
            }
            if ($bool) {
                return $supportedType;
            }
        }

        return $defaultType;
    }

    /**
     * parse media type string
     *
     * e.g., parseMediaType('foo/bar; baz=qux; quux=corge')
     * [
     *     "type" => "foo",
     *     "subtype" => "bar",
     *     "parameters" => [
     *         "baz" => "qux",
     *         "quux" => "corge",
     *     ],
     * ]
     *
     * @param string $mediaType
     * @return array media assoc
     */
    public static function parseMediaType($mediaType) {
      $media = array_map('trim', explode(';', $mediaType));
      list($type, $subtype) = explode('/', array_shift($media));
      $params = [];
      foreach($media as $keyval) {
        list($key, $val) = array_map('trim', explode('=', $keyval, 2));

        $params[$key] = $val;
      }

      return [
        'type' => $type,
        'subtype' => $subtype,
        'parameters' => $params,
      ];
    }

    protected static function formatMediaType($mediaType) {
        $media = static::parseMediaType($mediaType);

        $ret = $media['type'] . '/' . $media['subtype'];

        ksort($media['parameters']);

        foreach ($media['parameters'] as $key => $val) {
            $ret .= ';' . $key . '=' . $val;
        }

        return $ret;
    }

    /**
     * Negotiate MimeType for `Accept` HTTP header
     *
     * @param array  $supportedTypes
     * @param string $defaultType    default: null
     * @return mixed negotiated MimeType or defaultType
     */
    public static function negotiateAcceptType(array $supportedTypes, $defaultType = null) {
        return static::negotiateMimeTypeString($_SERVER['HTTP_ACCEPT'], $supportedTypes, $defaultType);
    }

    /**
     * Negotiate MimeType for `Accept` HTTP header
     *
     * @param string $acceptStr
     * @param array  $supportedTypes
     * @param string $defaultType    default: null
     * @return mixed negotiated MimeType or defaultType
     */
    public static function negotiateAcceptTypeString($acceptStr, array $supportedTypes, $defaultType = null) {
        if (empty($supportedTypes)) {
            return $defaultType;
        }

        $supportedMap = [];
        foreach ($supportedTypes as $type) {
            $mediaRange = static::formatMediaType(strtolower($type));
            $supportedMap[$mediaRange] = $type;
        }
        
        $acceptTypes = static::parseAcceptTypes($acceptStr);

        foreach ($acceptTypes as $mediaRange) {
            if ($type = static::getMatchedSupportedType($supportedMap, $mediaRange)) {
                return $type;
            }
        }
        
        return $defaultType;
    }

    protected static function getMatchedSupportedType($supportedMap, $mediaRange) {
        if (isset($supportedMap[$mediaRange])) {
            return $supportedMap[$mediaRange];
        }

        list($type) = explode(';', $mediaRange, 2);

        if ($type === '*/*') {
            return reset($supportedMap);
        }

        list($general, $subtype) = explode('/', $type, 2);
        if ($subtype === '*') {
            $general .= '/';
            foreach($supportedMap as $key => $mime) {
                if (strpos($key, $general) === 0) {
                    return $mime;
                }
            }
        }

        return null;
    }

    /**
     * Parse HTTP Accept header value
     * 
     * @param string $acceptStr
     * @return array sorted media-ranges
     */
    public static function parseAcceptTypes($acceptStr) {
        $acceptStr = strtolower($acceptStr);
        $acceptTypes = [];

        $ls = explode(',', $acceptStr);
        $cnt = count($ls);

        foreach ($ls as $i => $mediaStr) {
            $media = static::parseAcceptMediaRange($mediaStr);

            $acceptTypes[$media['media-range']] = [
                'q' => $media['q'],
                'specificity' => $media['specificity'],
                'priority' => $cnt -$i,
            ];
        }

        uasort($acceptTypes, function($a, $b){
            if ($a['q'] !== $b['q']) {
                return $b['q'] - $a['q'];
            }

            if ($a['specificity'] !== $b['specificity']) {
                return $b['specificity'] - $a['specificity'];
            }

            return $b['priority'] - $a['priority'];
        });
        
        return array_keys($acceptTypes);
    }

    protected static function parseAcceptMediaRange($mediaRange) {
        $media = static::parseMediaType($mediaRange);
        $type = $media['type'];
        $subtype = $media['subtype'];
        $params = $media['parameters'];

        $range = $type . '/' . $subtype;

        if (isset($params['q'])) {
            $q = $params['q'] * 100;
        } elseif ($range == '*/*') {
            $q = 1;
        } elseif ($subtype === '*') {
            $q = 2;
        } else {
            $q = 100;
        }

        $rangeParam = [];
        foreach ($params as $key => $val) {
            if ($key === 'q') {
                break;
            }
            $rangeParam[$key] = $key . '=' . $val;
        }

        if ($rangeParam) {
            ksort($rangeParam);
            $range .= ';' . implode(';', $rangeParam);
        }

        return [
            'type' => $type,
            'subtype' => $subtype,
            'q' => $q,
            'media-range' => $range,
            'specificity' => count($rangeParam),
        ];
    }

    protected static function _parseAcceptMediaRange($mediaRange) {
        $media = array_map('trim', explode(';', $mediaRange));
        $type = array_shift($media);
        $q = null;

        $params = [];
        foreach ($media as $keyval) {
            list($key, $val) = array_map('trim', explode('=', $keyval, 2));
            if ($key === 'q') {
                $q = (float) $val;
                break;
            }
            $params[$key] = "${key}=${val}";
        }
        ksort($params);

        if ($q) {
            $q *= 100;
        } elseif ($type == '*/*') {
            $q = 1;
        } elseif (substr($type, -1) === '*') {
            $q = 2;
        } else {
            $q = 100;
        }

        $range = $type;
        if (!empty($params)) {
            $range .= ';' . implode(';', $params);
        }

        return [
            'type' => $type,
            'q' => $q,
            'media-range' => $range,
            'specificity' => count($params),
        ];
    }
}
