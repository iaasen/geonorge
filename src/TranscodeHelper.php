<?php
/**
 * User: ingvar.aasen
 * Date: 2025-06-04
 */

namespace Iaasen\Geonorge;

use Iaasen\Geonorge\Entity\LocationLatLong;
use Iaasen\Geonorge\Entity\LocationUtm;
use Iaasen\Geonorge\Rest\TranscodeService;
use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

class TranscodeHelper
{
    private static Proj4php $proj4php;
    private static Proj $utm33;
    private static Proj $etrs89;
    private static TranscodeService $transcodeService; // Geonorge

    public static function convertUtm33ToEtrs89UsingProj4php(LocationUtm $locationUtm): LocationLatLong
    {
        static::initProj4php();
        $utm33Point = new Point($locationUtm->utm_east, $locationUtm->utm_north, static::$utm33);
        $etrs89Point = static::$proj4php->transform(static::$etrs89, $utm33Point);
        return new LocationLatLong($etrs89Point->y, $etrs89Point->x);
    }

    public static function convertUtm33ToEtrs89UsingGeonorge(LocationUtm $locationUtm): LocationLatLong
    {
        static::initTranscodeService();
        return static::$transcodeService->transcodeUTMtoLatLong($locationUtm->utm_north, $locationUtm->utm_east, $locationUtm->utm_zone);
    }

    public static function convertErts89ToUtm33UsingProj4php(LocationLatLong $locationLatLong): LocationUtm
    {
        static::initProj4php();
        $etrs89Point = new Point($locationLatLong->longitude, $locationLatLong->latitude, static::$etrs89);
        $utm33Point = static::$proj4php->transform(static::$utm33, $etrs89Point);
        return new LocationUtm(round($utm33Point->y, 2), round($utm33Point->x, 2), '33N');
    }

    public static function convertEtrs89ToUtm33UsingGeonorge(LocationLatLong $locationLatLong): LocationUtm
    {
        static::initTranscodeService();
        return static::$transcodeService->transcodeLatLongToUTM($locationLatLong->latitude, $locationLatLong->longitude, 33);
    }

    private static function initProj4php(): void
    {
        if(!isset(static::$proj4php)) static::$proj4php = new Proj4php();
        if(!isset(static::$utm33)) static::$utm33 = new Proj('EPSG:25833', static::$proj4php);
        if(!isset(static::$etrs89)) static::$etrs89 = new Proj('EPSG:4258', static::$proj4php);
    }

    private static function initTranscodeService(): void
    {
        if(!isset(static::$transcodeService)) static::$transcodeService = new TranscodeService();
    }

}
