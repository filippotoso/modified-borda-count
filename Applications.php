<?php

namespace FilippoToso\ModifiedBordaCount;

use FilippoToso\ModifiedBordaCount\Application;

class Applications {

    private $container = [];

    /**
     * Add a new application to the repository
     * @method add
     * @param  Application $application The application to add
     */
    public function add(Application $application) {
        if (array_key_exists($application->id, $this->container)) {
            return FALSE;
        }
        $this->container[$application->id] = $application;
        return TRUE;
    }

    /**
     * Remove an application to the repository by $id
     * @method remove
     * @param  string $id The application id
     */
    public function remove($id) {
        if (!array_key_exists($id, $this->container)) {
            return FALSE;
        }
        unset($this->container[$id]);
        return TRUE;
    }

    /**
     * Update the order of the internal container's array
     * @method updateOrder
     * @param  Array       $globalList  The global list of ids & votes
     * @return Array
     */
    protected function updateOrder(Array $globalList) {

        arsort($globalList);

        $order = 0;
        foreach ($globalList as $id => $vote) {
            $this->container[$id]->order = $order;
            $this->container[$id]->vote = $vote;
            $order++;
        }

        return $globalList;

    }

    /**
     * Calculat a list of applications (id / vote) depending of each applcations reviews
     * @method globalList
     * @return Array
     */
    public function globalList() {

        $counts = [];

        $result = [];

        foreach ($this->container as $application) {

            $localList = $application->localList();

            foreach ($localList as $id => $vote) {
                $counts[$id] = isset($counts[$id]) ? $counts[$id] + 1 : 1;
                $result[$id] = isset($result[$id]) ? $result[$id] + $vote : $vote;
            }

        }

        // Normalize votes depending on the number of
        // votes each application has been rated for
        $max = max($counts);
        foreach ($result as $id => &$vote) {
            $vote = ($vote * $max) / $counts[$id];
        }

        return $this->updateOrder($result);

    }

    /**
     * Order the internal container by order, vote and merit
     * @method orderContainer
     * @return Array         An array of id / order applications
     */
    protected function orderContainer() {

        uasort($this->container, function($a, $b) {
            if ($a->order == $b->order) {
                if ($a->vote == $b->vote) {
                    if ($a->merit == $b->merit) {
                        return 0;
                    }
                    return ($a->merit > $b->merit) ? -1 : 1;
                }
                return ($a->vote > $b->vote) ? -1 : 1;
            }
            return ($a->order > $b->order) ? -1 : 1;
        });

        $order = 0;
        $result = [];
        foreach ($this->container as $current) {
            $current->order = $order;
            $result[$current->id] = $current->order;
            $order++;
        }

        return $result;

    }

    /**
     * Calculate and unbiased global list removing the votes for a specific application reviews
     * @method unbiasedGlobalList
     * @param  Array              $localList  The specific applications reviews' votes
     * @param  Array              $globalList The global list of ids / votes
     * @return Array
     */
    protected function unbiasedGlobalList(Array $localList, Array $globalList) {

        foreach ($localList as $id => $vote) {
            $globalList[$id] = isset($globalList[$id]) ? $globalList[$id] - $vote : 0;
            $globalList[$id] = $globalList[$id] > 0 ? $globalList[$id] : 0;
        }

        return $globalList;

    }

    /**
     * Calculate the relative list of the element in a local list from an unbiased global list
     * @method globalRelativeList
     * @param  Array              $localList          The specific applications reviews' votes
     * @param  Array              $unbiasedGlobalList The unbiased global list of ids / votes
     * @return Array
     */
    protected function globalRelativeList(Array $localList, Array $unbiasedGlobalList) {

        $result = [];

        foreach ($localList as $id => $vote) {
            // If $unbiasedGlobalList[$id] doesn't exists there's a problem somewehere else
            $result[$id] = $unbiasedGlobalList[$id];
        }

        arsort($result);

        $i = 0;
        $total = count($result) - 1;
        foreach ($result as &$vote) {
            $vote = $total - $i;
            $i++;
        }

        return $result;

    }

    /**
     * Calcilate the absolute different ranke between a local list and a relative global list
     * @method calculateAbsRank
     * @param  Array            $localList          The specific local list
     * @param  Array            $globalRelativeList The relative global list related to the provided local list
     * @return Array
     */
    protected function calculateAbsRank(Array $localList, Array $globalRelativeList) {

        $result = 0;

        foreach ($localList as $id => $vote) {
            $result += abs($localList[$id] - $globalRelativeList[$id]);
        }

        return $result;

    }

    /**
     * Calculate the merit for each applicants in the internal container
     * @method calculateMerit
     * @param  Array          $globalList The global list
     */
    protected function calculateMerit(Array $globalList) {

        foreach ($globalList as $id => $vote) {
            $this->calculateSingleMerit($globalList, $this->container[$id]);
        }

        return TRUE;

    }

    /**
     * Calculate the merit of a single applicant
     * @method calculateSingleMerit
     * @param  Array                $globalList  The global list
     * @param  Application          $application The specific application for which you want calculate the merit.
     * @return Real                              The calculated merit
     */
    protected function calculateSingleMerit(Array $globalList, Application $application) {

        $localList = $application->localList();

        $unbiasedGlobalList = $this->unbiasedGlobalList($localList, $globalList);
        $globalRelativeList = $this->globalRelativeList($localList, $unbiasedGlobalList);

        $absRank =$this->calculateAbsRank($localList, $globalRelativeList);
        $count = count($localList);
        $application->merit = 1 - ((1 / floor(1 / 2 * $count * $count)) * $absRank);

        return $application->merit;

    }

    /**
    * Calculate the ranked list depending on each applicant merit and input parameters
     * @method rankedList
     * @param  float      $meritWorst     The merit below which the applicant will be demoted for $meritWorstDown positions
     * @param  float      $meritBest      The merit above which the applicant will be promoted for $meritBestUp positions
     * @param  integer    $meritWorstDown How much the applicant will be demoted if his merit is lower than $meritWorst
     * @param  integer    $meritBestUp    How much the applicant will be promoted if his merit is higher than $meritBestUp
     * @return Array
     */
    public function rankedList($meritWorst = 0.3, $meritBest = 0.6, $meritWorstDown = 10, $meritBestUp = 10) {

        $globalList = $this->globalList();

        $this->calculateMerit($globalList);

        arsort($globalList);

        $order = 0;
        foreach ($globalList as $id => $vote) {
            $this->container[$id]->vote = $vote;
            $this->container[$id]->order = $order;
            $order++;
        }

        foreach ($this->container as $application) {
            if ($application->merit > $meritBest) {
                $application->order -= $meritBestUp;
            } elseif ($application->merit < $meritWorst) {
                $application->order += $meritWorstDown;
            }
        }

        return $this->orderContainer();

    }

    public function set($name, Application $value) {

        if (is_a($value, Application::class)) {
            $this->container[$name] = $value;
        }

        return FALSE;

    }

    public function get($name) {

        if (array_key_exists($name, $this->container)) {
            return $this->container[$name];
        }

        return null;

    }


}
