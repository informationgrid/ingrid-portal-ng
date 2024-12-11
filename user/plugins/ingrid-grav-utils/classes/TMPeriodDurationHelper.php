<?php

namespace Grav\Plugin;

class TMPeriodDurationHelper
{

    public static function transformPeriodDuration(string $duration, string $lang): string
    {
        $value = "";
        try {
            $dateInterval = new \DateInterval($duration);

            if($dateInterval->format('%y') != 0)
            {
                $value .= $dateInterval->format('%y ' . CodelistHelper::getCodelistEntry(['1230'], '6', $lang));
            }
            if($dateInterval->format('%m') != 0)
            {
                $value .= $dateInterval->format('%m ' . CodelistHelper::getCodelistEntry(['1230'], '5', $lang));
            }
            if($dateInterval->format('%d') != 0)
            {
                $value .= $dateInterval->format('%d ' . CodelistHelper::getCodelistEntry(['1230'], '4', $lang));
            }
            if($dateInterval->format('%h') != 0)
            {
                $value .= $dateInterval->format('%h ' . CodelistHelper::getCodelistEntry(['1230'], '3', $lang));
            }
            if($dateInterval->format('%i') != 0)
            {
                $value .= $dateInterval->format('%i ' . CodelistHelper::getCodelistEntry(['1230'], '2', $lang));
            }
            if($dateInterval->format('%s') != 0)
            {
                $value .= $dateInterval->format('%s ' . CodelistHelper::getCodelistEntry(['1230'], '1', $lang));
            }
        } catch (\Exception $e){
            return $duration;
        }
        return $value ?? $duration;
    }
}