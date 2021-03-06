<?php namespace Acte\ContentManager\Models;

use Model;
use Log;
use Acte\ContentManager\Models\FieldType;
use Config;


/**
 * Model
 */
class Field extends Model
{

    public $thumbDefaults; //config array

    public function __construct(){
      $this->thumbDefaults = Config::get('acte.contentmanager::config.thumbnail', []);
    }

    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];
    public $translatable = ['data'];

    protected $appends = ['content'];

    public $timestamps = false;

    public $table = 'acte_contentmanager_fields';

    public $belongsTo = [
      'element' => ['Acte\ContentManager\Models\Element', 'key' => 'element_id'],
      'field_type' => ['Acte\ContentManager\Models\FieldType', 'key' => 'field_type_id'],
      'format' => ['Acte\ContentManager\Models\Format', 'key' => 'format_id']

    ];

    public $attachOne = [
      'image' => 'System\Models\File'
    ];

    public $attachMany = [
      'images' => 'System\Models\File'
    ];

    public $rules = [
      'code' => 'required|alpha_dash',
      'field_type_id' => 'required|numeric',
      'image' => 'nullable|image|max:2MB',
      'images' => 'required',
      'images.*' => 'nullable|image|max:2MB'
    ];

    public function setCodeAttribute($value){
        $this->attributes['code'] = str_replace([' ', '-'], '_', $value);;
    }

    public function getContentAttribute(){


      if($this->field_type){

        switch ($this->field_type->code) {
          case 'string'|'text'|'textarea'|'richeditor'|'number':
            return $this->data;
            break;

          case 'image':

            if( $this->image ){


                $thumb = $this->image->getThumb($this->thumbDefaults['width'],$this->thumbDefaults['height'],'crop');
                $path = $this->image->path;

                if($this->format_id){
                  $format = $this->format;

                  $width = $format->width;
                  $height = $format->height;
                  $mode = $format->mode;
                  $quality = $format->quality;

                  if($this->image->getWidthAttribute() > $width){
                    $path = $this->image->getThumb($width, $height, ['mode' => $mode, 'quality' => $quality]);
                  }
                }

            } else {
              $path = null;
              $thumb = null;
            }

            $image = [
              'path' => $path,
              'thumb' => $thumb,
            ];

            return $image;


            break;

          case 'images':

            $array = [];
            if( $this->images() ){

              foreach ($this->images as $key => $image) {

                $thumb = $image->getThumb($this->thumbDefaults['width'],$this->thumbDefaults['height'],'crop');
                $path = $image->path;

                if($this->format_id){
                  $format = $this->format;

                  $width = $format->width;
                  $height = $format->height;
                  $mode = $format->mode;
                  $quality = $format->quality;

                  if($image->getWidthAttribute() > $width){
                    $path = $image->getThumb($width, $height, ['mode' => $mode, 'quality' => $quality]);
                  }
                }

                $array[] = [ 'path' => $path, 'thumb' => $thumb ];

              }
            }

            return $array;
            break;

          default:
            break;
        }
      }

      return $this->data;

    }


    public function filterFields($fields, $context = null)
    {

        $fields->data->hidden = true;
        $fields->image->hidden = true;
        $fields->format->hidden = true;
        $fields->images->hidden = true;

        if(isset($fields->field_type) && $fields->field_type->value){

          $fieldTypeCode = FieldType::findOrFail($fields->field_type->value)->code;

          switch ($fieldTypeCode) {
            case 'string':
              $fields->data->hidden = false;
              $fields->data->type = 'text';
              break;

            case 'number':
              $fields->data->hidden = false;
              $fields->data->type = 'number';
              break;

            case 'textarea':
              $fields->data->hidden = false;
              $fields->data->type = 'textarea';
              break;

            case 'richeditor':
              $fields->data->hidden = false;
              // set as default in yaml config
              break;

            case 'image':
              $fields->image->hidden = false;
              $fields->format->hidden = false;
              break;

            case 'images':
              $fields->images->hidden = false;
              $fields->format->hidden = false;
              break;

            default:
              $fields->data->type = 'text';
              break;
          }

        }
    }

}
