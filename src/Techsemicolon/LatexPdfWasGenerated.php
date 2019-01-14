<?php

namespace Techsemicolon;

class LatexPdfWasGenerated
{
    /**
     * Path of pdf
     * 
     * @var string
     */
    public $pdf;

    /**
     * Type of action download|savepdf
     * @var string
     */
    public $action;

    /**
     * Create a new event instance.
     *
     * @param string $pdf
     * 
     * @return void
     */
    public function __construct($pdf, $action = 'download')
    {
        $this->pdf      = $pdf;
        $this->action   = $action;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}