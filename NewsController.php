<?php

namespace App\Http\Controllers\Admin;

use App\leagues;
use App\matches;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\News;
use App\news_image;
use App\news_category;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class NewsController extends Controller
{

    public function get_news(){

        $data=News::join('news_category', 'news.category_id', '=', 'news_category.id')
            ->select('news.*', 'news_category.category_az')->get();

        return view('admin.news',compact('data'));

    }
    public function getMatchLeague($id){

        $matches=matches::where('league_id',$id)->join('allteams','matches.rival_id','=','allteams.id')
            ->select('matches.*','allteams.team_az')
            ->get();

        $result['matches'] = $matches;

        echo json_encode($result);
        exit;


    }
    public function get_newsmatch($id){

        $data=News::where('match_id',$id)->get();
        $match_id=$id;
        return view('admin.matchnews',compact('data','match_id'));

    }
    public function get_addmatchnews($id){

        $match_id=$id;

        return view('admin.addmatchnews',compact('match_id'));

    }
    public function  get_addnews(){

        $matches=matches::orderBy('date','desc')->get();
        $leagues=leagues::orderBy('start_end','desc')->get();

        $data=news_category::where('type','news')->where('status',false)->orderBy('position','asc')->get();
        return view('admin.addnews',compact('data','matches','leagues'));


    }
    public function get_deletenewsimage($id){

        $data=news_image::find($id);
        if($data->delete()) return back();


    }


    public function  get_editnews($id){


        $data=News::find($id);
        $images=news_image::where('news_id',$id)->get();
        $category=news_category::where('type','news')->where('status',false)->orderBy('position','asc')->get();

        $matches=matches::orderBy('date','desc')->get();
        return view('admin.editnews',compact('data','images','category','matches'));

    }
    public function  get_editmatchnews($match_id,$id){


        $data=News::find($id);
        $images=news_image::where('news_id',$id)->get();


        return view('admin.editmatchnews',compact('data','images'));

    }

    public function  get_deletenews($id){

        $data=News::find($id);
        news_image::where('news_id',$id)->delete();
        if($data->delete()) return redirect('/adminpanel/news');

    }

    public function  get_deletematchnews($match_id,$id){

        $data=News::find($id);
        news_image::where('news_id',$id)->delete();
        if($data->delete()) return redirect('/adminpanel/matchnews/'.$match_id);

    }

    public function  post_addnews(Request $request){

        $top_panel=$request->top_panel ? $request->top_panel : 0;
        $heading_az=$request->heading_az ? $request->heading_az : "";
        $heading_ru=$request->heading_ru ? $request->heading_ru : "";
        $heading_en=$request->heading_en ? $request->heading_en : "";

        $content_az=$request->content_az ? $request->content_az : "" ;
        $content_ru=$request->content_ru  ? $request->content_ru : "";
        $content_en=$request->content_en  ? $request->content_en : "";

        $category_id=$request->category_id ? $request->category_id : "";

        $match_id=$request->news_match ? $request->news_match : 0;
        if($match_id) $news_match=1; else $news_match=0;

        $publish_time=$request->publish_time ;

        $date = $request->date ;

        $video=$request->video  ? $request->video : "";

        $slug=str_slug('neftchi-news-'.Carbon::now());

        $mainimage="";
        $file_main=$request->file('mainimage');

        if($file_main)
        {
            $file_name=$file_main->getClientOriginalName();
            $file_extension=$file_main->getClientOriginalExtension();
            $type = ["png","jpg","gif","bmp","jpeg","webp"];
            if(in_array($file_extension,$type))
            {
                $myfile =  str_slug($heading_az.'-'.Carbon::now()) . '.' . $file_extension;

                if($file_main->move('uploads/news',$myfile))  $mainimage=$myfile; else $mainimage="";
            }
        }

        $addnews=new News;


        $addnews->heading_az=$heading_az;
        $addnews->heading_ru=$heading_ru;
        $addnews->heading_en=$heading_en;
        $addnews->top_panel=$top_panel;
        $addnews->content_az=$content_az;
        $addnews->content_ru=$content_ru;
        $addnews->content_en=$content_en;

        $addnews->category_id=$category_id;

        $addnews->match_id=$match_id;
        $addnews->news_match=$news_match;

        $addnews->publish_time=$publish_time;
        $addnews->date=$date;
        $addnews->mainimage=$mainimage;
        $addnews->video=$video;
        $addnews->slug=$slug;

        $addnews->save();


        $news_id=$addnews->id;
        $news_publish_time=News::find($news_id)->publish_time;


        $content = array(
            "en" => $heading_en,
            "az"=> $heading_az,
            "ru"=> $heading_ru,

        );
        $hashes_array = array();
        $fields = array(
            'app_id' => "7c494edb-290b-41a4-8cba-55c1f54e94bd",
            'included_segments' => array(
                'All'
            ),
            'send_after' => $news_publish_time." GMT+0400",
            //'delayed_option'=>'Asia\Baku',
            'data' => array(
                "heading" => "news",
                "news_id"=>$news_id
            ),
            'contents' => $content,
            'web_buttons' => $hashes_array
        );

        $fields = json_encode($fields);
        print("\nJSON sent:\n");
        print($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic NzlmOTE4NWMtMWIyNi00NjE4LTlmNWUtYTg4MDNjZTQxZGQx'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);
        // return $response;


        $file_others= $request->file('sliderimages');
        if($file_others)

        {
            foreach ($file_others as $new) {


                $name=$new->getClientOriginalName();
                $extension=$new->getClientOriginalExtension();
                $type = ["png","jpg","gif","bmp","jpeg","webp"];
                if(in_array($extension,$type))
                {
                    $otherfile = str_slug($name.'-'.Carbon::now()) . '.' . $extension;

                    if($new->move('uploads/news',$otherfile))
                    {
                        $add_news_image=new news_image;
                        $add_news_image->image=$otherfile;
                        $add_news_image->news_id=$news_id;


                        $add_news_image->save();

                    }

                }
            }
        }



        return redirect('adminpanel/news');

    }

    public function  post_addmatchnews(Request $request,$id){

        $heading_az=$request->heading_az ? $request->heading_az : "";
        $heading_ru=$request->heading_ru ? $request->heading_ru : "";
        $heading_en=$request->heading_en ? $request->heading_en : "";
        $content_az=$request->content_az ? $request->content_az : "" ;
        $content_ru=$request->content_ru  ? $request->content_ru : "";
        $content_en=$request->content_en  ? $request->content_en : "";

        $publish_time=$request->publish_time ;
        $date = $request->date ;
        $video=$request->video  ? $request->video : "";
        $slug=str_slug('neftchi-news-'.Carbon::now());

        $mainimage="";
        $file_main=$request->file('mainimage');

        if($file_main)
        {
            $file_name=$file_main->getClientOriginalName();
            $file_extension=$file_main->getClientOriginalExtension();
            $type = ["png","jpg","gif","bmp","jpeg","webp"];
            if(in_array($file_extension,$type))
            {
                $myfile =  str_slug($heading_az.'-'.Carbon::now()) . '.' . $file_extension;

                if($file_main->move('uploads/news',$myfile))  $mainimage=$myfile; else $mainimage="";
            }
        }

        $addnews=new News;


        $addnews->heading_az=$heading_az;
        $addnews->heading_ru=$heading_ru;
        $addnews->heading_en=$heading_en;
        $addnews->content_az=$content_az;
        $addnews->content_ru=$content_ru;
        $addnews->content_en=$content_en;

        $addnews->publish_time=$publish_time;
        $addnews->date=$date;
        $addnews->mainimage=$mainimage;
        $addnews->video=$video;
        $addnews->match_id=$id;
        $addnews->slug=$slug;

        $addnews->save();

        $news_id=$addnews->id;

        $file_others= $request->file('sliderimages');
        if($file_others)

        {
            foreach ($file_others as $new) {


                $name=$new->getClientOriginalName();
                $extension=$new->getClientOriginalExtension();
                $type = ["png","jpg","gif","bmp","jpeg"];
                if(in_array($extension,$type))
                {
                    $otherfile = str_slug($name.'-'.Carbon::now()) . '.' . $extension;

                    if($new->move('uploads/news',$otherfile))
                    {
                        $add_news_image=new news_image;
                        $add_news_image->image=$otherfile;
                        $add_news_image->news_id=$news_id;


                        $add_news_image->save();

                    }

                }
            }
        }

        return redirect('adminpanel/matchnews/'.$id);

    }
    public function  post_editnews(Request $request,$id)
    {

        $main_image = $request->file('mainimage');
        $slider_images = $request->file('sliderimages');
        $type = ["png", "jpg", "gif", "bmp", "jpeg","webp"];

        $match_id=$request->news_match ? $request->news_match : 0;
        if($match_id) $news_match=1; else $news_match=0;

        if ($slider_images){
            foreach ($slider_images as $si){
                $file_name = $si->getClientOriginalName();
                $file_extension = $si->getClientOriginalExtension();
                if (in_array($file_extension, $type)) {
                    $si_name = str_slug($si->getClientOriginalName() . '-' . Carbon::now()) . '.' . $si->getClientOriginalExtension();
                }
                $si = $si->move('uploads/news',$si_name);
                news_image::create(['image' => $si_name, 'news_id' =>$id]);
            }
        }

        if ($main_image){

            $file_name = $main_image->getClientOriginalName();
            $file_extension = $main_image->getClientOriginalExtension();
            if (in_array($file_extension, $type)) {
                $main_image_name =  str_slug($main_image->getClientOriginalName() . '-' .Carbon::now()) . '.' . $main_image->getClientOriginalExtension();
            }
            $main_image = $main_image->move('uploads/news',$main_image_name);
            $news = News::where('id',$id)->update([
                'mainimage' => $main_image_name,
            ]);
        }
        $top_panel=$request->top_panel ? $request->top_panel : 0;
        $news = News::where('id',$id)->update([
            'heading_az' => $request->get('heading_az'),
            'heading_en' => $request->get('heading_en'),
            'heading_ru' => $request->get('heading_ru'),
            'content_az' => $request->get('content_az'),
            'content_en' => $request->get('content_en'),
            'content_ru' => $request->get('content_ru'),
            'category_id' => $request->get('category_id'),
            'match_id'=>$match_id,
            'news_match'=>$news_match,
            'publish_time' => $request->get('publish_time'),
            'top_panel' => $top_panel,
            'date' => $request->get('date'),
            'slug' => str_slug('neftchi-news'.Carbon::now()),
            'video' => $request->get('video')
        ]);
        return $this->get_news();
    }

    public function  post_editmatchnews(Request $request,$match_id,$id)
    {

        $main_image = $request->file('mainimage');
        $slider_images = $request->file('sliderimages');
        $type = ["png", "jpg", "gif", "bmp", "jpeg","webp"];

        if ($slider_images){
            foreach ($slider_images as $si){
                $file_name = $si->getClientOriginalName();
                $file_extension = $si->getClientOriginalExtension();
                if (in_array($file_extension, $type)) {
                    $si_name = str_slug($si->getClientOriginalName() . '-' . Carbon::now()) . '.' . $si->getClientOriginalExtension();
                }
                $si = $si->move('uploads/news',$si_name);
                news_image::create(['image' => $si_name, 'news_id' =>$id]);
            }
        }

        if ($main_image){

            $file_name = $main_image->getClientOriginalName();
            $file_extension = $main_image->getClientOriginalExtension();
            if (in_array($file_extension, $type)) {
                $main_image_name = str_slug($main_image->getClientOriginalName() . '-' . Carbon::now()) . '.' . $main_image->getClientOriginalExtension();
            }
            $main_image = $main_image->move('uploads/news',$main_image_name);
            $news = News::where('id',$id)->update([
                'mainimage' => $main_image_name,
            ]);
        }
        $news = News::where('id',$id)->update([
            'heading_az' => $request->get('heading_az'),
            'heading_en' => $request->get('heading_en'),
            'heading_ru' => $request->get('heading_ru'),
            'content_az' => $request->get('content_az'),
            'content_en' => $request->get('content_en'),
            'content_ru' => $request->get('content_ru'),
            'slug' => str_slug('neftchi-news'.Carbon::now()),
            'publish_time' => $request->get('publish_time'),
            'date' => $request->get('date'),
            'video' => $request->get('video')
        ]);
        return redirect('/adminpanel/matchnews/'.$match_id);
    }

   


}
