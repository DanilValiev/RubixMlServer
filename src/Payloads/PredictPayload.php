<?php

namespace Rubix\Server\Payloads;

/**
 * Predict Payload
 *
 * This is the response returned from a predict command containing the
 * predictions returned from the model.
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class PredictPayload extends Payload
{
    /**
     * The preditions returned from the model.
     *
     * @var mixed[]
     */
    protected $predictions;

    /**
     * @param mixed[] $predictions
     */
    public function __construct(array $predictions)
    {
        $this->predictions = $predictions;
    }

    /**
     * Return the predictions.
     *
     * @return mixed[]
     */
    public function predictions() : array
    {
        return $this->predictions;
    }

    /**
     * Return the message as an array.
     *
     * @return mixed[]
     */
    public function asArray() : array
    {
        return [
            'data' => $this->predictions,
        ];
    }
}
