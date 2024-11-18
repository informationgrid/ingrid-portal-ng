<?php

namespace Grav\Plugin;

use GuzzleHttp\Client;

class MimeTypeHelper
{
    public static function getUrlMimetype(string $url): string
    {
        if (str_contains(strtolower($url), "service=csw")) {
            $extension = "csw";
        } else if(str_contains(strtolower($url), "service=wms")) {
            $extension = "wms";
        } else if(str_contains(strtolower($url), "service=wfs")) {
            $extension = "wfs";
        } else if(str_contains(strtolower($url), "service=wcs")) {
            $extension = "wcs";
        } else if(str_contains(strtolower($url), "service=wmts") || str_contains(strtolower($url), "wmtscapabilities.xml")) {
            $extension = "wmts";
        }

        if (empty($extension)) {
            if (str_starts_with($url, "http://") || str_starts_with($url, "https://")) {
                try {
                    $headers = get_headers($url, true);
                    $hasStatusOk = false;

                    foreach ($headers as $key => $head) {
                        if (is_numeric($key)) {
                            if (str_contains($head, " 20")) {
                                $hasStatusOk = true;
                                break;
                            }
                        }
                    }

                    if ($hasStatusOk) {
                        $headerContentType = $headers["Content-Type"];
                        if (!empty($headerContentType)) {
                            if (is_array($headerContentType)) {
                                $contentType = $headerContentType[count($headerContentType) - 1];
                            } else {
                                $contentType = $headerContentType;
                            }
                            $mimeTypeExtension = self::getMimetypeExtension(explode(";", $contentType)[0]);
                            if ($mimeTypeExtension) {
                                $extension = $mimeTypeExtension;
                            }
                        }
                    }
                } catch(\Exception $e) {
                    throw new \Exception($e);
                }
            }
        }
        return $extension ?? "";
    }

    public static function getMimetypeExtension(string $mime): bool|string
    {
        $mime_map = [
            "application/andrew-inset" => "ez",
            "application/excel" => "xls",
            "application/mac-binhex40" => "hqx",
            "application/mac-compactpro" => "cpt",
            "application/mathml+xml" => "mathml",
            "application/msword" => "doc",
            "application/octet-stream" => "bin",
            "application/oda" => "oda",
            "application/ogg" => "ogg",
            "application/pdf" => "pdf",
            "application/pgp" => "pgp",
            "application/pgp-encrypted" => "pgp",
            "application/pgp-keys" => "pgp",
            "application/pgp-signature" => "sig",
            "application/postscript" => "ps",
            "application/rdf+xml" => "rdf",
            "application/rdf" => "rdf",
            "application/rtf" => "rtf",
            "application/smil" => "smil",
            "application/srgs" => "gram",
            "application/srgs+xml" => "grxml",
            "application/vnd.mif" => "mif",
            "application/vnd.mozilla.xul+xml" => "xul",
            "application/vnd.ms-excel" => "xls",
            "application/vnd.ms-powerpoint" => "ppt",
            "application/vnd.ms-project" => "mpp",
            "application/vnd.ms-tnef" => "tnef",
            "application/vnd.rn-realmedia" => "rm",
            "application/vnd.wap.wbxml" => "wbxml",
            "application/vnd.wap.wmlc" => "wmlc",
            "application/vnd.wap.wmlscriptc" => "wmlsc",
            "application/voicexml+xml" => "vxml",
            "application/x-arj-compressed" => "arj",
            "application/x-bcpio" => "bcpio",
            "application/x-cdlink" => "vcd",
            "application/x-chess-pgn" => "pgn",
            "application/x-compress" => "Z",
            "application/x-cpio" => "cpio",
            "application/x-csh" => "csh",
            "application/x-debian-package" => "deb",
            "application/x-director" => "dir",
            "application/x-dvi" => "dvi",
            "application/x-futuresplash" => "spl",
            "application/x-gtar" => "gtar",
            "application/x-gunzip" => "gz",
            "application/x-gzip" => "gz",
            "application/x-hdf" => "hdf",
            "application/x-javascript" => "js",
            "application/x-koan" => "skp",
            "application/x-latex" => "latex",
            "application/x-mif" => "mif",
            "application/x-msdos-program" => "exe",
            "application/x-netcdf" => "cdf nc",
            "application/x-netcdf" => "nc",
            "application/x-perl" => "pl pm",
            "application/x-python" => "py",
            "application/x-rar-compressed" => "rar",
            "application/x-sh" => "sh",
            "application/x-shar" => "shar",
            "application/x-shockwave-flash" => "swf",
            "application/x-stuffit" => "sit",
            "application/x-sv4cpio" => "sv4cpio",
            "application/x-sv4crc" => "sv4crc",
            "application/x-tar" => "tar",
            "application/x-tar-gz" => "tgz",
            "application/x-tcl" => "tcl",
            "application/x-tex" => "tex",
            "application/x-texinfo" => "texinfo",
            "application/x-troff" => "troff",
            "application/x-troff-man" => "man",
            "application/x-troff-me" => "me",
            "application/x-troff-ms" => "ms",
            "application/x-ustar" => "ustar",
            "application/x-wais-source" => "src",
            "application/x-zip-compressed" => "zip",
            "application/xhtml+xml" => "xhtml",
            "application/xhtml" => "xhtml",
            "application/xml" => "xml",
            "application/xml-dtd" => "dtd",
            "application/xslt+xml" => "xslt",
            "application/xslt" => "xslt",
            "application/zip" => "zip",
            "audio/basic" => "au",
            "audio/midi" => "mid",
            "audio/mpeg" => "mpg",
            "audio/ulaw" => "au",
            "audio/x-aiff" => "aif",
            "audio/x-mpegurl" => "m3u",
            "audio/x-pn-realaudio" => "ram",
            "audio/x-wav" => "wav",
            "audio/x-midi" => "mid",
            "audio/x-mpeg" => "mpg",
            "chemical/x-pdb" => "pdb",
            "chemical/x-xyz" => "xyz",
            "image/bmp" => "bmp",
            "image/cgm" => "cgm",
            "image/g3fax" => "fax",
            "image/gif" => "gif",
            "image/ief" => "ief",
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/svg+xml" => "svg",
            "image/tiff" => "tif",
            "image/vnd.djvu" => "djvu",
            "image/vnd.wap.wbmp" => "wbmp",
            "image/x-cmu-raster" => "ras",
            "image/x-icon" => "ico",
            "image/x-portable-anymap" => "pnm",
            "image/x-portable-bitmap" => "pbm",
            "image/x-portable-graymap" => "pgm",
            "image/x-portable-pixmap" => "ppm",
            "image/x-rgb" => "rgb",
            "image/x-xbitmap" => "xbm",
            "image/x-xpixmap" => "xpm",
            "image/x-xwindowdump" => "xwd",
            "model/iges" => "igs",
            "model/mesh" => "msh",
            "model/vrml" => "vrml",
            "text/calendar" => "ics",
            "text/css" => "css",
            "text/html" => "html",
            "text/plain" => "txt",
            "text/richtext" => "rtx",
            "text/rtf" => "rtf",
            "text/sgml" => "sgml",
            "text/tab-separated-values" => "tsv",
            "text/vnd.wap.wml" => "wml",
            "text/vnd.wap.wmlscript" => "wmls",
            "text/x-setext" => "etx",
            "text/xml" => "xml",
            "video/dl" => "dl",
            "video/fli" => "fli",
            "video/gl" => "gl",
            "video/mpeg" => "mpg",
            "video/quicktime" => "mov",
            "video/vnd.mpegurl" => "mxu",
            "video/x-msvideo" => "avi",
            "video/x-sgi-movie" => "movie",
            "x-conference/x-cooltalk" => "ice",
            "x-world/x-vrml" => "vrml",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "xlsx",
            "application/json" => "json",
            "application/vnd.rar" => "rar",
            "application/x-7z-compressed" => "7z",
            "application/x-bzip" => "bz",
            "text/csv" => "csv",
            "text/css" => "css",
            "application/x-bzip2" => "bz2",
        ];
        return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
    }
}