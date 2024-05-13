<?php

namespace m2m\seeding;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;

class M2MSeeding
{
    /*
        * first model we want to seed it
        */
    private string $firstModel;

    /*
     * second model we want to seed it
     */
    private string $secondModel;

    /*
     * relation from first model to second model
     */
    private string $relation;

    /*
     *  number of instance needed for first model
     *  by default no instance needed
     */
    private int $countFirstModel = 0;

    /*
     *  number of instance needed for second model
     *  by default no instance needed
     */
    private int $countSecondModel = 0;

    /*
     *  minimum number of relation needed between two models
     *  by default is 0
     */
    private int $minRelation = 0;

    /*
     *  maximum number of relation needed between two models
     *  by default is 3
     */
    private int $maxRelation = 3;

    /*
     *  pivot attributes in Join table as array
     *  kye is the name of field
     *  value is array define 'type' and ( 'in' [values...] or 'range' [min, max] )
     */
    private ?Closure $pivotCallback = null;


    private function __construct($firstModel, $secondModel, $relation)
    {
        $this->firstModel = $firstModel;
        $this->secondModel = $secondModel;
        $this->relation = $relation;
    }

    private static function checkIfIsModel($className): void
    {
        if (!is_subclass_of($className, Model::class)) {
            throw new Exception("the $className must be a Model");
        }
    }

    public static function make(string $firstModel, string $secondModel, string $relation): M2MSeeding
    {
        self::checkIfIsModel($firstModel);
        self::checkIfIsModel($secondModel);
        return new M2MSeeding($firstModel, $secondModel, $relation);
    }


    private function checkIfLessThanZero(int $count): void
    {
        if ($count < 0) {
            throw new Exception('the count must be equal or greater than 0');
        }
    }

    public function withFactory(int $countOfFirstModel, int $countOfSecondModel): M2MSeeding
    {
        $this->checkIfLessThanZero($countOfFirstModel);
        $this->checkIfLessThanZero($countOfSecondModel);

        $this->countFirstModel = $countOfFirstModel;
        $this->countSecondModel = $countOfSecondModel;

        return $this;
    }

    public function minRelation(int $count): M2MSeeding
    {
        $this->checkIfLessThanZero($count);
        $this->minRelation = $count;
        return $this;
    }

    public function maxRelation(int $count): M2MSeeding
    {
        $this->checkIfLessThanZero($count);
        $this->maxRelation = $count;
        return $this;
    }

    public function rangeRelation(int $min, int $max): M2MSeeding
    {
        $this->minRelation($min);
        $this->maxRelation($max);
        return $this;
    }

    private function checkCallback($callback): void
    {
        if(!is_callable($callback)){
            throw new Exception('the argument must be a callback');
        }
        if(!is_array($callback())){
            throw new Exception('the callback must return an array');
        }
    }

    public function withPivot($callback): M2MSeeding{

        $this->checkCallback($callback);
        $this->pivotCallback = $callback;
        return $this;
    }

    private function factory(): void
    {
        if ($this->countFirstModel > 0) {
            logger($this->firstModel);
            $this->firstModel::factory($this->countFirstModel)->create();
        }

        if ($this->countSecondModel > 0) {
            $this->secondModel::factory($this->countSecondModel)->create();
        }
    }


    private function getRandomArray($array, $count): array{
        $arrayKeys = array_rand($array, $count);
        $randomArray = [];
        if(!is_array($arrayKeys)){
            $randomArray[] = $array[$arrayKeys];
        }else {
            foreach ($arrayKeys as $item) {
                $randomArray[] = $array[$item];
            }
        }

        return $randomArray;
    }

    private function addPivot($array): array{
        $attachedWithPivot = [];
        $callback = $this->pivotCallback;
        if($callback){
            foreach ($array as $value){
                $attachedWithPivot[$value] = $callback();
            }
        }else{
            $attachedWithPivot = $array;
        }
        return $attachedWithPivot;
    }

    private function getAttachedArray($ids)
    {

        $randomNumber = rand($this->minRelation, $this->maxRelation);
        if($randomNumber == 0){
            return null;
        }
        $attachedArray = $this->getRandomArray($ids, $randomNumber);
        return $this->addPivot($attachedArray);
    }

    private function checkBeforeRun(): void{
        if($this->minRelation > $this->maxRelation){
            throw new Exception('minimum relation must be less than maximum relation');
        }
        $countSecond = $this->secondModel::count() + $this->countSecondModel;
        if($this->maxRelation > $countSecond){
            throw new Exception("maximum relation must be less than or equal $this->secondModel count");
        }
    }

    public function run(): void{
        $this->checkBeforeRun();
        $this->factory();
        $models = $this->firstModel::all();
        $relation = $this->relation;
        $ids = $this->secondModel::select('id')->get()->pluck('id')->toArray();
        foreach ($models as $model){
            $attached = $this->getAttachedArray($ids);
            if(! $attached) continue;
            $model->$relation()->attach($attached);
        }
    }
}
