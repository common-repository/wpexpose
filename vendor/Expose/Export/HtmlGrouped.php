<?php

namespace Expose\Export;

class HtmlGrouped extends \Expose\Export
{
    public function render()
    {
        $lines = array();
        $data = $this->getData();
        if (is_array($data)) {
            foreach ($data as $path=>$v) {
                $line = '<p>';
                $line .= '<h3>Variable:: '.$path . '</h3>';
                $line .= '<p><strong>Value:</strong>: '. htmlentities( $v['value'] ) . '</p>';
                $line .= '<p><strong>Path:</strong>: '.json_encode($path) . '</p>';
                $line .= '<p><strong>Filters:</strong></p>';
                $line .= "<dl>";
                foreach ($v['filters'] as $filter) {
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