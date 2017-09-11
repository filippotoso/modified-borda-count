<?php

namespace FilippoToso\ModifiedBordaCount;

class Application {

    private $id = FALSE;
    private $data = [];

    private $reviews = [];
    private $merit = 0;
    private $order = null;
    private $vote = null;

    public function __construct($id = FALSE) {
        $this->id = $id;
    }

    /**
     * Add a review to this applicaton
     * @method addReview
     * @param  Application $application The application you want this applicant to review
     */
    public function addReview(Application $application) {
        $this->reviews[] = $application;
    }

    /**
     * Generate a local list of reviewed applications
     * @method localList
     * @return Array
     */
    public function localList() {

        $result = [];

        $order = 0;
        $total = count($this->reviews) - 1;
        foreach ($this->reviews as $review) {
            $result[$review->id] = $total - $order;
            $order++;
        }

        return $result;

    }

    public function __set($name, $value)
    {

        if ($name == 'id') {
            $this->id = $value;
        } elseif ($name == 'merit') {
            $this->merit = $value;
        } elseif ($name == 'order') {
            $this->order = $value;
        } elseif ($name == 'vote') {
            $this->vote = $value;
        }

        if (array_key_exists($name, $this->data)) {
            $this->data[$name] = $value;
        }

    }

    public function __get ( $name ) {

        if ($name == 'id') {
            return $this->id;
        } elseif ($name == 'merit') {
            return $this->merit;
        } elseif ($name == 'order') {
            return $this->order;
        } elseif ($name == 'vote') {
            return $this->vote;
        }

        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;

    }

    public function __isset($name) {

        return in_array($name, ['id']) ? TRUE : array_key_exists($name, $this->data);

    }

    public function __unset($name) {

        if ($name == 'id') {
            $this->id = null;
        } elseif ($name == 'order') {
            $this->order = null;
        } elseif ($name == 'vote') {
            $this->vote = 0;
        } else {
            unset($this->data[$name]);
        }

    }

}
