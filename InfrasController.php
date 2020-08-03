<?php

namespace App\Http\Controllers\Admin;

use App\Menu;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\infrastructure;
use App\gallery_photo;
use App\gallery_video;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class InfrasController extends Controller
{
    public function get_infrastructur(){

        $data=infrastructure::where('is_menu',0)->get();
        return view('admin.infrastructure',compact('data'));

    }

    public function get_addinfrastructur(){


        return view('admin.addinfrastructure');

    }

    public function get_editinfrastructur($id){

        $data=infrastructure::find($id);
        $photos=gallery_photo::where('type','LIKE','%infrastructur/'.$data->slug.'%')->get();
        $videos=gallery_video::where('type','LIKE','%infrastructur/'.$data->slug.'%')->get();


        return view('admin.editinfrastructure',compact('photos','videos','data'));

    }

    public function get_deletephoto($id){

        $data=gallery_photo::find($id)->delete();
        return back();
    }

    public function get_deleteinfrastructur($id){
        $data=infrastructure::find($id);

        $videos=gallery_video::where('type','LIKE','%infrastructur/'.$data->slug.'%')->delete();
        $images=gallery_photo::where('type','LIKE','%infrastructur/'.$data->slug.'%')->delete();

        $menu_id=$data->menu_id;
        if($data->is_menu==0)
        {
            $data->delete();
            return redirect('adminpanel/infrastructur');
        }
        if($data->is_menu==1)
        {
            $menu_delete=Menu::find($menu_id)->delete();
            if($menu_delete) $data->delete();
            return redirect('adminpanel/infrasmenu');
        }


    }



    public function post_addinfrastructur(Request $request){

        $title_az=$request->title_az ? $request->title_az : "";
        $iframe=$request->iframe ? $request->iframe : "";
        $title_en=$request->title_en ? $request->title_en : "";
        $title_ru=$request->title_ru ? $request->title_ru : "";
        $text_az=$request->text_az ? $request->text_az : "";
        $text_en=$request->text_en ? $request->text_en : "";
        $text_ru=$request->text_ru ? $request->text_ru : "";
        $slug=str_slug($title_en);

        if($request->title_az ) {
            $mainimage="";
            $file_main=$request->file('mainimage');

            if($file_main)
            {
                $file_name=$file_main->getClientOriginalName();
                $file_extension=$file_main->getClientOriginalExtension();
                $type = ["png","jpg","gif","bmp","jpeg","webp"];
                if(in_array($file_extension,$type))
                {

                    $image= str_slug($title_az.rand(99999,1000000).Carbon::now()).'.'.$file_extension;





                    if( $file_main->move('uploads/club/infrastructur',$image)) $mainimage=$image;
                    else $mainimage="";
                }
            }

            $file_others= $request->file('gallery');
            if($file_others)

            {
                foreach ($file_others as $new) {


                    $name=$new->getClientOriginalName();
                    $extension=$new->getClientOriginalExtension();
                    $type = ["png","jpg","gif","bmp","jpeg","webp"];
                    if(in_array($extension,$type))
                    {
                        $otherfile = str_slug($title_az.rand(99999,1000000).Carbon::now()).'.'.$extension;

                        if( $new->move('uploads/club/infrastructur',$otherfile)){



                            $add_main_image=new gallery_photo;
                            $add_main_image->image=$otherfile;
                            $add_main_image->type='infrastructur/'.$slug;
                            $add_main_image->save();


                        }

                    }
                }
            }


            $video=$request->video_url;
            if($video)
            {
                foreach ($video as $key => $items) {

                    if($items){
                        $add_video=new gallery_video;
                        $add_video->url=$items;
                        $add_video->type='infrastructur/'.$slug;

                        $add_video->save();
                    }
                }

            }



            $data=new infrastructure;
            $data->iframe=$iframe;
            $data->title_az=$title_az;
            $data->title_ru=$title_ru;
            $data->title_en=$title_en;
            $data->text_az=$text_az;
            $data->text_ru=$text_ru;
            $data->text_en=$text_en;
            $data->mainimage=$mainimage;
            $data->slug=$slug;
            if($data->save()) return redirect('/adminpanel/infrastructur');
        }


        else
            return view('admin.addinfrastructure');

    }

    public function post_editinfrastructur(Request $request,$id){
        $data=infrastructure::find($id);
        if($request->title_az && $request->text_az) {

            $title_az=$request->title_az ? $request->title_az : $data->title_az;
            $iframe=$request->iframe ? $request->iframe : $data->iframe;
            $title_en=$request->title_en ? $request->title_en : $data->title_en;
            $title_ru=$request->title_ru ? $request->title_ru : $data->title_ru;
            $text_az=$request->text_az ? $request->text_az : $data->text_az;
            $text_en=$request->text_en ? $request->text_en : $data->text_en;
            $text_ru=$request->text_ru ? $request->text_ru : $data->text_ru;
            if($data->slug!='museum' && $data->slug!='stadion') $slug=str_slug($title_en); else $slug=$data->slug;


            $mainimage=$data->mainimage;
            $file_main=$request->file('mainimage');

            if($file_main)
            {
                $file_name=$file_main->getClientOriginalName();
                $file_extension=$file_main->getClientOriginalExtension();
                $type = ["png","jpg","gif","bmp","jpeg","webp"];
                if(in_array($file_extension,$type))
                {
                    $myfile =  $file_name;
                    $image= str_slug($title_az.rand(99999,1000000).Carbon::now()).'.'.$file_extension;





                    if( $file_main->move('uploads/club/infrastructur',$image)) $mainimage=$image;
                    else $mainimage=$data->mainimage;
                }
            }

            $file_others= $request->file('gallery');
            if($file_others)

            {
                foreach ($file_others as $new) {


                    $name=$new->getClientOriginalName();
                    $extension=$new->getClientOriginalExtension();
                    $type = ["png","jpg","gif","bmp","jpeg","webp"];
                    if(in_array($extension,$type))
                    {
                        $otherfile = str_slug($title_az.rand(99999,1000000).Carbon::now()).'.'.$extension;


                        $add_main_image=new gallery_photo;
                        $add_main_image->image=$otherfile;
                        $add_main_image->type='infrastructur/'.$slug;


                        if($add_main_image->save()) $new->move('uploads/club/infrastructur',$otherfile);

                    }
                }
            }


            $video=$request->video_url;

            if($video>0)
            {

                $videos=gallery_video::where('type','like','%infrastructur/'.$data->slug.'%')->delete();

                foreach ($video as $key => $items) {
                    if($items)
                    {
                        $add_video=new gallery_video;
                        $add_video->url=$items;
                        $add_video->type='infrastructur/'.$slug;

                        $add_video->save();
                    }
                }

            }

            $gallery=gallery_photo::where('type','infrastructur/'.$data->slug)
                ->update(['type'=>'infrastructur/'.$slug]);


            $data->iframe=$iframe;
            $data->title_az=$title_az;
            $data->title_ru=$title_ru;
            $data->title_en=$title_en;
            $data->text_az=$text_az;
            $data->text_ru=$text_ru;
            $data->text_en=$text_en;
            $data->mainimage=$mainimage;
            $data->slug=$slug;
            if($data->save()){
                if($data->is_menu==0)  return redirect('/adminpanel/infrastructur');
                if($data->is_menu==1)  return redirect('/adminpanel/infrasmenu');
            }
        }


        else
            return redirect('/adminpanel/editinfrastructure/'.$id);

    }



}
