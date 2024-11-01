<?php

namespace Expose\Export;

class Html extends \Expose\Export
{
    public function render()
    {
        $lines = array();
        $data = $this->getData();
        if (is_array($data)) {
            foreach ($data as $report) {
                $line = '<dl>';
                $line .= '<dt><strong>Variable:</strong></dt><dd>'.$report->getVarName() . '</dd>';
                $line .= '<dt><strong>Value:</strong></dt><dd>'. htmlentities( $report->getVarValue() ) . '</dd>';
                $line .= '<dt><strong>Path:</strong></dt><dd>'.json_encode($report->getVarPath()) . '</dd>';

                foreach ($report->getFilterMatch() as $filter) {
                    $line .= '<dt><strong>ID:</strong></dt><dd>'. $filter->getId(). '</dd>';
                    $line .= '<dt><strong>Description:</strong></dt><dd>('.$filter->getId().') '.$filter->getDescription() . '</dd>';
                    $line .= '<dt><strong>Impact:</strong></dt><dd>'.$filter->getImpact() . '</dd>';
                    $line .= '<dt><strong>Tags:</strong></dt><dd>'.implode(', ', $filter->getTags()) . '</dd>';
                }
                $line .= "</dl>";
                $lines[] = $line;
            }
        }
        return implode("", $lines);
    }
}