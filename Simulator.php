<?php

namespace FilippoToso\ModifiedBordaCount;

use FilippoToso\ModifiedBordaCount\Application;
use FilippoToso\ModifiedBordaCount\Applications;

class Simulator {

    /**
     * Order the applications depending on the type of simulated user
     * @method order
     * @param  Array   $applications The applications
     * @param  String  $type         The type of simulated user
     * @param  integer $delta        The delta for competent users
     * @return Array                 The ordered applications
     */
    protected function order(Array $applications, $type, $delta = 10) {

        if ($type == 'competent') {

            // Sort results in the correct order except when two values are
            // separated by less than $delta. In which case order by random
            usort($applications, function($a, $b) use ($delta) {

                $diff = abs($a - $b);

                if ($diff <= $delta) {
                    return (rand() < getrandmax() / 2) ? 1 : -1;
                }

                return ($a < $b) ? -1 : 1;

            });

        } elseif ($type == 'incompetent') {

            shuffle($applications);

        } elseif ($type == 'evil') {

            rsort($applications);

        } elseif ($type == 'perfect') {

            sort($applications);

        }

        return $applications;

    }


    /**
     * Generate simulated applications
     * @method generate
     * @param  integer  $load        Number of applications evaluated by each applicant
     * @param  integer  $applicants  Total number of applicants
     * @param  integer  $competent   Number of competent applicants
     * @param  integer  $incompetent Number of incopetent applicants
     * @param  integer  $evil        Number of evil applicants
     * @param  integer  $perfect     Number of perfect applicants
     * @return Array
     */
    public function generate($load = 8, $applicants = 100, $competent = 85, $incompetent = 10, $evil = 5, $perfect = 0) {

        // Exit if improper values are provided
        if ($applicants != ($competent + $incompetent + $evil + $perfect)) {
            return FALSE;
        }

        $ordered = range(1, $applicants);
        $input = $ordered;

        $shuffled = [];
        for ($i = 0; $i <= $load * 2; $i++) {
            shuffle($input);
            $shuffled = array_merge($shuffled, $input);
        }

        $types = array_merge(array_fill(0, $competent, 'competent'), array_fill(0, $incompetent, 'incompetent'), array_fill(0, $evil, 'evil'), array_fill(0, $perfect, 'perfect'));

        $evaluated = [];
        $evaluations = [];
        $applicant = 1;

        foreach ($types as $type) {

            $evaluations[$applicant] = [];

            while (count($evaluations[$applicant]) < $load) {

                $current = array_shift($shuffled);

                // Avoid conflict of interests
                if ($current == $applicant) {
                    continue;
                }

                // Avoid duplicates
                if (in_array($current, $evaluations[$applicant])) {
                    continue;
                }

                 $evaluations[$applicant][] = $current;

            }

            $evaluated[$applicant] = $this->order($evaluations[$applicant], $type);

            $applicant++;

        }

        $applications = new Applications();

        foreach ($evaluations as $id => $evaluation) {
            $application = new Application(sprintf('#%03d', $id));
            $application->value = $id;
            $applications->add($application);
        }

        foreach ($evaluated as $id => $items) {
            $current = $applications->get(sprintf('#%03d', $id));
            foreach ($items as $item) {
                $current->addReview($applications->get(sprintf('#%03d', $item)));
            }
        }

        $rankedList = $applications->rankedList();

        $chartList = [];
        foreach ($rankedList as $id => $value) {
            $chartList[(int)substr($id, 1)] = round($value);
        }
        return $chartList;

    }

    /**
     * Dispaly a chart with the result of the simulated Modified Borda Count
     * @method display
     * @param  Array   $chartList The out put of generate()
     * @return void
     */
    public function display(Array $chartList) {

        $size = count($chartList);

        $im = imagecreatetruecolor($size, $size);

        $blue = imagecolorallocate($im, 0, 0, 255);

        $red = imagecolorallocate($im, 255, 0, 0);

        for ($x = 0; $x < $size; $x++) {
            if (isset($chartList[$x + 1])) {
                imagesetpixel($im, $x, $chartList[$x + 1], $red);
            }
        }

        header('Content-Type: image/png');
        imagepng($im);
        imagedestroy($im);

    }


}
