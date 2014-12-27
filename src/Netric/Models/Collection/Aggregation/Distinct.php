<?php
/**
 * Terms aggregate will get distinct distinct values with document count
 * 
 * Returned array is
 * 
 * array(
 *   array(
 *       "count"=>num,
 *       "term"=>term
 *   ),
 * )
 * 
 * @author Sky Stebnicki <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */

namespace Netric\Models\Collection\Aggregation;

/**
 * Terms aggreagation will gather terms and counts from a field
 */
class Distinct extends AbstractAggregation implements AggregationInterface
{
    //put your code here
}
