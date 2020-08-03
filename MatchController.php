<?php

namespace App\Http\Controllers\Admin;

use App\comment_types;
use App\FootballerStatistics;
use App\match_comments;
use App\match_gallery;
use App\match_videos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\leagues;
use App\league_result_table;
use App\allteams;
use App\Teams;
use App\matches;
use App\match_results;
use App\Footballers;
use App\all_team_players;
use App\match_footballers_result;
use App\match_action_table;
use Illuminate\Support\Facades\File;


class MatchController extends Controller
{

    public function get_matches()
    {
        $matches = matches::all();

        return view('admin.matches', compact('matches'));

    }
    public function is_matchteam_player($footballer_id,$match_id){

        $league_id=matches::where('id',$match_id)->first()->league_id;
        $matchteam_id=leagues::where('id',$league_id)->first()->neftchi_team_id;

        $is_match_player=false;

        $add_footballer=Footballers::where('id',$footballer_id)->get();
        $is_footballer=count($add_footballer);
        if($is_footballer!=0) {

            if($add_footballer->first()->team_id==$matchteam_id) $is_match_player=true;
        }

        return $is_match_player;

    }

    public function get_deletematch($id)
    {
        $action_data = match_action_table::where('match_id', $id)->where('is_rival', 0)->get();

        $league_id=matches::where('id',$id)->first()->league_id;
        $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
        $league_season=leagues::where('id',$league_id)->first()->start_end;

        foreach ($action_data as $items) {

            if ($items->action == "yellow_card")

            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true)
                {
                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['yellow_card' => DB::raw('yellow_card-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['yellow_card' => DB::raw('yellow_card-1')]);
                }
            }

            if ($items->action == "red_card")
            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true) {
                    FootballerStatistics::where('footballer_id', $items->footballer_id)
                        ->where('league_name_id', $league_name_id)->
                        where('league_season', $league_season)->
                        update(['red_card' => DB::raw('red_card-1')]);


                    Footballers::where('id', $items->footballer_id)->
                    update(['red_card' => DB::raw('red_card-1')]);
                }
            }

            if ($items->action == "goal")

            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true) {
                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['goals' => DB::raw('goals-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['goals_number' => DB::raw('goals_number-1')]);
                }
            }
            if ($items->action == "pen")

            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true) {
                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['goals' => DB::raw('goals-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['goals_number' => DB::raw('goals_number-1')]);
                }
            }


            if ($items->action == "asists") {

                if($this->is_matchteam_player($items->footballer_id,$id)==true) {

                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['asists' => DB::raw('asists-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['asists_number' => DB::raw('asists_number-1')]);
                }
            }

        }

        $delete_action = match_action_table::where('match_id', $id)->delete();

        $player_result_data = match_footballers_result::where('match_id', $id)->where('is_rival', 0)->get();

        if ($player_result_data) {
            foreach ($player_result_data as $items) {

                $played = $items->played ? $items->played : 0;
                $starts=$items->starts;
                $subs_off=$items->subs_off;
                $missed_goal=$items->missed_goal;
                $dry_game=$items->dry_game;

                $edit_statistic= FootballerStatistics::where('footballer_id',$items->footballers_id)
                    ->where('league_name_id',$league_name_id)->
                    where('league_season',$league_season)->update([

                        'played_neftchi'=>DB::raw('played_neftchi-1'),
                        'time_field'=>DB::raw('time_field-' . $played),
                        'starts'=>DB::raw('starts-' . $starts),
                        'subs_off'=>DB::raw('subs_off-' . $subs_off),
                        'missed_goal'=>DB::raw('missed_goal-' . $missed_goal),
                        'dry_game'=>DB::raw('dry_game-' . $dry_game)

                    ]);

                Footballers::where('id', $items->footballers_id)->
                update(['number_games_neftchi' => DB::raw('number_games_neftchi-1'),
                    'time_field' => DB::raw('time_field-' . $played),
                    'time_field_total' => DB::raw('time_field_total-' . $played)
                ]);

            }
        }
        $delete_players = match_footballers_result::where('match_id', $id)->delete();

        $matches = matches::find($id);
        $league_id = $matches->league_id;
        $rival_id = $matches->rival_id;

        $neftchi_results = match_results::where('match_id', $id)->where('is_neftchi', 1)->first();
        $oldscore = $neftchi_results->score;
        $rival_results = match_results::where('match_id', $id)->where('is_neftchi', 0)->first();
        $roldscore = $rival_results->score;


        league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
        update(['p' => DB::raw('p-1'), 'gs' => DB::raw('gs-' . $oldscore), 'ga' => DB::raw('ga-' . $roldscore)]);

        league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
        update(['p' => DB::raw('p-1'), 'gs' => DB::raw('gs-' . $roldscore), 'ga' => DB::raw('ga-' . $oldscore)]);

        if ($oldscore > $roldscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['pts' => DB::raw('pts-3'), 'w' => DB::raw('w-1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['l' => DB::raw('l-1')]);
        }

        if ($oldscore < $roldscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['pts' => DB::raw('pts-3'), 'w' => DB::raw('w-1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['l' => DB::raw('l-1')]);
        }
        if ($oldscore == $roldscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['pts' => DB::raw('pts-1'), 'd' => DB::raw('d-1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['pts' => DB::raw('pts-1'), 'd' => DB::raw('d-1')]);
        }

        $delete_matchresults = match_results::where('match_id', $id)->delete();


        $delete_match = matches::find($id)->delete();


        return redirect('/adminpanel/matches');

    }

    public function get_delete_neftchi_lineup($id){

        $league_id=matches::where('id',$id)->first()->league_id;
        $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
        $league_season=leagues::where('id',$league_id)->first()->start_end;




        $player_result_data = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 1)->where('is_rival', 0)->get();
        if ($player_result_data) {
            foreach ($player_result_data as $items) {

                $played = $items->played ? $items->played : 0;
                $starts=$items->starts;
                $subs_off=$items->subs_off;
                $missed_goal=$items->missed_goal;
                $dry_game=$items->dry_game;

                $edit_statistic= FootballerStatistics::where('footballer_id',$items->footballers_id)
                    ->where('league_name_id',$league_name_id)->
                    where('league_season',$league_season)->update([

                        'played_neftchi'=>DB::raw('played_neftchi-1'),
                        'time_field'=>DB::raw('time_field-' . $played),
                        'starts'=>DB::raw('starts-' . $starts),
                        'subs_off'=>DB::raw('subs_off-' . $subs_off),
                        'missed_goal'=>DB::raw('missed_goal-' . $missed_goal),
                        'dry_game'=>DB::raw('dry_game-' . $dry_game)

                    ]);

                Footballers::where('id', $items->footballers_id)->
                update(['number_games_neftchi' => DB::raw('number_games_neftchi-1'),
                    'time_field' => DB::raw('time_field-' . $played),
                    'time_field_total' => DB::raw('time_field_total-' . $played)
                ]);

            }
        }
        $delete_line_up=match_results::where('match_id',$id)->where('is_neftchi',1)->
        update([
            'row' => 0,
            'formalization'=>""
        ]);
        $delete_players = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 1)->where('is_rival', 0)->delete();
        if($delete_line_up && $delete_players)
            return redirect('/adminpanel/editmatchplayers/'.$id);
    }

    public function get_delete_rival_lineup($id){

        $player_result_data = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 1)->where('is_rival', 1)->get();
        if($player_result_data){
            $delete_line_up=match_results::where('match_id',$id)->where('is_neftchi',0)->
            update([
                'row' => 0,
                'formalization'=>""
            ]);
            $delete_players = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 1)->where('is_rival', 1)->delete();
            if($delete_line_up && $delete_players)
                return redirect('/adminpanel/editmatchplayers/'.$id);
        }
    }

    public function get_editmatchplayers($id)
    {


        $match = matches::find($id);

        $league = leagues::find($match->league_id);
        $data = match_results::where('match_id', $id)->where('is_neftchi', 1)->first();
        $row_neftchi = $data->row;
        $rival_id = $match->rival_id;

        $neftchi_team = Footballers::where('team_id', $league->neftchi_team_id)->get();
        $rival_team = all_team_players::where('team_id', $rival_id)->get();
        $neftchi_results = match_footballers_result::where('match_id', $id)->where('is_rival', 0)->
        where('is_mainfootballer', 1)->get();


        $rival_results = match_footballers_result::where('match_id', $id)->where('is_rival', 1)->
        where('is_mainfootballer', 1)->get();

        $neftchi_subteam = match_footballers_result::where('match_id', $id)->where('is_rival', 0)->
        where('is_mainfootballer', 0)->get();

        $rival_subteam = match_footballers_result::where('match_id', $id)->where('is_rival', 1)->
        where('is_mainfootballer', 0)->get();

        $neftchi_line_up=match_results::where('match_id', $id)->where('is_neftchi', 1)->first();
        $rival_line_up=match_results::where('match_id', $id)->where('is_neftchi', 0)->first();
        $match_id=$id;
        return view('admin.editmatchplayers', compact('neftchi_team_id', 'rival_id', 'neftchi_team',
            'rival_team', 'neftchi_results', 'rival_results', 'rival_subteam', 'neftchi_subteam','neftchi_line_up'
            ,'rival_line_up','row_neftchi','match_id'));


    }


    public function get_editmatch($id)
    {

        $match = matches::find($id);
        $leagues = leagues::all();
        $rivals = allteams::all();
        $neftchi_team_id = leagues::find($match->league_id);
        $neftchiteam = Teams::find($neftchi_team_id->neftchi_team_id);
        $neftchi_results = match_results::where('match_id', $id)->where('is_neftchi', 1)->first();
        $rival_results = match_results::where('match_id', $id)->where('is_neftchi', 0)->first();


        return view('admin.editmatch', compact('leagues', 'match', 'rivals', 'neftchiteam', 'neftchi_results',
            'rival_results'));
    }

    public function post_editmatch($id, Request $request)
    {

        $match = matches::find($id);
        $league_id = $request->get('league');
        $rival_id = $request->get('rival');
        $score = $request->score;
        $rscore = $request->rscore;
        $neftchi_results = match_results::where('match_id', $id)->where('is_neftchi', 1)->first();
        $oldscore = $neftchi_results->score;
        $rival_results = match_results::where('match_id', $id)->where('is_neftchi', 0)->first();
        $roldscore = $rival_results->score;
        $mainfile = $request->file('image');


        $mainimage = "";

        if ($mainfile) {
            $file_name = $mainfile->getClientOriginalName();
            $file_extension = $mainfile->getClientOriginalExtension();
            $type = ["png", "jpg", "gif", "bmp", "jpeg", "webp"];
            if (in_array($file_extension, $type)) {


                $mainimage = str_slug(rand(99999, 1000000) . Carbon::now()) . '.' . $file_extension;

                if (!$mainfile->move('uploads/awards/', $mainimage)) {

                    $mainimage = "";
                }
            }

        }
        if ($mainimage)
            $edit_match = matches::where('id', $id)->update([

                'image' => $mainimage
            ]);
        $is_home=$request->is_home ? 1 :0;
        $edit_match = matches::where('id', $id)->update([

            'league_id' => $league_id,
            'rival_id' => $rival_id,
            'is_home' => $is_home,
            'date' => $request->get('date'),
            'time' => $request->get('time'),
            'week' => $request->get('week'),
            'referee' => $request->get('referee'),
            'location' => $request->get('location'),


        ]);

        league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
        update(['gs' => DB::raw('gs-' . $oldscore . '+' . $score), 'ga' => DB::raw('ga-' . $roldscore . '+' . $rscore)]);

        league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
        update(['gs' => DB::raw('gs-' . $roldscore . '+' . $rscore), 'ga' => DB::raw('ga-' . $oldscore . '+' . $score)]);

        if ($oldscore > $roldscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['pts' => DB::raw('pts-3'), 'w' => DB::raw('w-1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['l' => DB::raw('l-1')]);
        }

        if ($oldscore < $roldscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['pts' => DB::raw('pts-3'), 'w' => DB::raw('w-1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['l' => DB::raw('l-1')]);
        }
        if ($oldscore == $roldscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['pts' => DB::raw('pts-1'), 'd' => DB::raw('d-1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['pts' => DB::raw('pts-1'), 'd' => DB::raw('d-1')]);
        }

        if ($score > $rscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['pts' => DB::raw('pts+3'), 'w' => DB::raw('w+1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['l' => DB::raw('l+1')]);
        }

        if ($score < $rscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['pts' => DB::raw('pts+3'), 'w' => DB::raw('w+1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['l' => DB::raw('l+1')]);
        }
        if ($score == $rscore) {

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['pts' => DB::raw('pts+1'), 'd' => DB::raw('d+1')]);

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['pts' => DB::raw('pts+1'), 'd' => DB::raw('d+1')]);
        }


        $edit_neftchi = match_results::where('match_id', $id)->where('is_neftchi', 1)->update([

            'possesion' => $request->get('possesion'),
            'shot' => $request->get('shot'),
            'ongoal' => $request->get('ongoal'),
            'fouls' => $request->get('fouls'),
            'redcard' => $request->get('redcard'),
            'yellowcard' => $request->get('yellowcard'),
            'offsides' => $request->get('offsides'),
            'corners' => $request->get('corners'),
            'saves' => $request->get('saves'),
            'score' => $request->get('score')

        ]);

        $edit_rival = match_results::where('match_id', $id)->where('is_neftchi', 0)->update([

            'possesion' => $request->get('rpossesion'),
            'shot' => $request->get('rshot'),
            'ongoal' => $request->get('rongoal'),
            'fouls' => $request->get('rfouls'),
            'redcard' => $request->get('rredcard'),
            'yellowcard' => $request->get('ryellowcard'),
            'offsides' => $request->get('roffsides'),
            'corners' => $request->get('rcorners'),
            'saves' => $request->get('rsaves'),
            'score' => $request->get('rscore')

        ]);




        return redirect('/adminpanel/matches');


    }

    public function get_editmatchactions($id)
    {

        $match = matches::where('id', $id)->first();
        $league_id = $match->league_id;
        $neftchi_team = leagues::where('id', $league_id)->first();
        $neftchi_team_id = $neftchi_team->neftchi_team_id;

        $neftchi_results = match_action_table::where('match_id', $id)->where('is_rival', 0)->get();
        $match_players=match_footballers_result::where('match_id',$id)->where('is_rival', 0)->get();

        $footballers = Footballers::where('team_id', $neftchi_team_id)->get();
        $rival_id = $match->rival_id;
        $rivals = all_team_players::where('team_id', $rival_id)->get();
        $rival = allteams::where('id', $rival_id);
        $rival_results = match_action_table::where('match_id', $id)->where('is_rival', 1)->get();


        return view('admin.editmatchactions', compact('neftchi_team', 'rival', 'footballers',
            'rivals', 'neftchi_results', 'rival_results'));


    }

    public function post_editmatchactions($id, Request $request)
    {
        $league_id=matches::where('id',$id)->first()->league_id;
        $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
        $league_season=leagues::where('id',$league_id)->first()->start_end;

        $action_data = match_action_table::where('match_id', $id)->where('is_rival', 0)->get();
        foreach ($action_data as $items) {

            if ($items->action == "yellow_card")

            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true)
                {
                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['yellow_card' => DB::raw('yellow_card-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['yellow_card' => DB::raw('yellow_card-1')]);
                }
            }

            if ($items->action == "red_card")
            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true) {
                    FootballerStatistics::where('footballer_id', $items->footballer_id)
                        ->where('league_name_id', $league_name_id)->
                        where('league_season', $league_season)->
                        update(['red_card' => DB::raw('red_card-1')]);


                    Footballers::where('id', $items->footballer_id)->
                    update(['red_card' => DB::raw('red_card-1')]);
                }
            }

            if ($items->action == "goal")

            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true) {
                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['goals' => DB::raw('goals-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['goals_number' => DB::raw('goals_number-1')]);
                }
            }
            if ($items->action == "pen")

            {
                if($this->is_matchteam_player($items->footballer_id,$id)==true) {
                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['goals' => DB::raw('goals-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['goals_number' => DB::raw('goals_number-1')]);
                }
            }


            if ($items->action == "asists") {

                if($this->is_matchteam_player($items->footballer_id,$id)==true) {

                    FootballerStatistics::where('footballer_id',$items->footballer_id)
                        ->where('league_name_id',$league_name_id)->
                        where('league_season',$league_season)->
                        update(['asists' => DB::raw('asists-1')]);

                    Footballers::where('id', $items->footballer_id)->
                    update(['asists_number' => DB::raw('asists_number-1')]);
                }
            }

        }

        $data = match_action_table::where('match_id', $id)->delete();


        $neftchi_action = $request->neftchi_action;
        $neftchi_action_footballer = $request->neftchi_action_footballer;
        $neftchi_action_time = $request->neftchi_action_time;

        foreach ($neftchi_action as $key => $array1) {

            if ($array1 && $neftchi_action_footballer[$key] && $neftchi_action_time[$key]) {
                $addneftchiaction = new match_action_table;

                $addneftchiaction->match_id = $id;
                $addneftchiaction->footballer_id = $neftchi_action_footballer[$key];
                $addneftchiaction->action = $array1;
                $addneftchiaction->is_rival = 0;
                $addneftchiaction->time = $neftchi_action_time[$key];
                if ($addneftchiaction->save())

                {

                    $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
                    $league_season=leagues::where('id',$league_id)->first()->start_end;
                    $footballer_id=$neftchi_action_footballer[$key];
                    $team_id=Footballers::where('id',$footballer_id)->first()->team_id;
                    $count=FootballerStatistics::where('footballer_id',$footballer_id)->where('league_name_id',$league_name_id)
                        ->where('league_season',$league_season)->get();



                    if ($array1 == "yellow_card")
                    {
                        if (count($count) == 1)

                            $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                ->where('league_name_id',$league_name_id)->
                                where('league_season',$league_season)->
                                update(['yellow_card' => DB::raw('yellow_card+1')]);

                        else
                            $add = FootballerStatistics::create([

                                'footballer_id' => $footballer_id,
                                'league_name_id' => $league_name_id,
                                'league_season' => $league_season,
                                'team_id' => $team_id,
                                'yellow_card'=>1

                            ]);

                    }

                    if ($array1 == "red_card")
                    {
                        if (count($count) == 1)

                            $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                ->where('league_name_id',$league_name_id)->
                                where('league_season',$league_season)->
                                update(['red_card' => DB::raw('red_card+1')]);

                        else
                            $add = FootballerStatistics::create([

                                'footballer_id' => $footballer_id,
                                'league_name_id' => $league_name_id,
                                'league_season' => $league_season,
                                'team_id' => $team_id,
                                'red_card'=>1

                            ]);

                    }

                    if ($array1 == "goal")
                    {
                        if (count($count) == 1)

                            $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                ->where('league_name_id',$league_name_id)->
                                where('league_season',$league_season)->
                                update(['goals' => DB::raw('goals+1')]);

                        else
                            $add = FootballerStatistics::create([

                                'footballer_id' => $footballer_id,
                                'league_name_id' => $league_name_id,
                                'league_season' => $league_season,
                                'team_id' => $team_id,
                                'goals'=>1

                            ]);

                    }

                    if ($array1 == "pen")
                    {
                        if (count($count) == 1)

                            $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                ->where('league_name_id',$league_name_id)->
                                where('league_season',$league_season)->
                                update(['goals' => DB::raw('goals+1')]);

                        else
                            $add = FootballerStatistics::create([

                                'footballer_id' => $footballer_id,
                                'league_name_id' => $league_name_id,
                                'league_season' => $league_season,
                                'team_id' => $team_id,
                                'goals'=>1

                            ]);

                    }

                    if ($array1 == "asists")
                    {
                        if (count($count) == 1)

                            $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                ->where('league_name_id',$league_name_id)->
                                where('league_season',$league_season)->
                                update(['asists' => DB::raw('asists+1')]);

                        else
                            $add = FootballerStatistics::create([

                                'footballer_id' => $footballer_id,
                                'league_name_id' => $league_name_id,
                                'league_season' => $league_season,
                                'team_id' => $team_id,
                                'asists'=>1

                            ]);

                    }


                }

            }
        }


        $rival_action = $request->rival_action;
        $rival_action_footballer = $request->rival_action_footballer;
        $rival_action_time = $request->rival_action_time;

        foreach ($rival_action as $key => $array1) {

            if ($array1 && $rival_action_footballer[$key] && $rival_action_time[$key]) {
                $addrivalaction = new match_action_table;

                $addrivalaction->match_id = $id;
                $addrivalaction->footballer_id = $rival_action_footballer[$key];
                $addrivalaction->action = $array1;
                $addrivalaction->is_rival = 1;
                $addrivalaction->time = $rival_action_time[$key];
                $addrivalaction->save();

            }
        }


        return redirect('/adminpanel/matches');

    }

    public function get_addmatches()
    {
        $leagues = leagues::all();
        $rivals = allteams::all();
        $teams=Teams::all();

        return view('admin.addmatches', compact('leagues', 'rivals','teams'));

    }

    public function get_setNeftchiTeam($id)
    {
        $data = leagues::where('id', $id)->first();
        $neftchi_team_id = $data->neftchi_team_id;
        $datateam = Teams::where('id', $neftchi_team_id)->first();
        $result['neftchiteam'] = $datateam->team_az;
        $result['neftchiplayers'] = Footballers::where('team_id', $neftchi_team_id)->get();

        echo json_encode($result);
        exit;

    }

    public function get_setRivalTeam($id)
    {

        $result['rivalplayers'] = all_team_players::where('team_id', $id)->get();

        echo json_encode($result);
        exit;

    }


    public function post_addmatches(Request $request)
    {

        $league_id = $request->league ? $request->league : 0;
        $neftchi_team_id = leagues::find($league_id);
        $mainfile = $request->file('image');
        $mainimage = "";

        if ($mainfile) {
            $file_name = $mainfile->getClientOriginalName();
            $file_extension = $mainfile->getClientOriginalExtension();
            $type = ["png", "jpg", "gif", "bmp", "jpeg", "webp"];
            if (in_array($file_extension, $type)) {


                $mainimage = str_slug(rand(99999, 1000000) . Carbon::now()) . '.' . $file_extension;

                if (!$mainfile->move('uploads/awards/', $mainimage)) {

                    $mainimage = "";
                }
            }

        }
        $date = $request->date ? $request->date : '0000-00-00';
        $hour = $request->hour ? $request->hour : 00;
        $minutes = $request->minutes ? $request->minutes : 00;
        $location = $request->location ? $request->location : "";
        $week = $request->week ? $request->week : "";
        $referee = $request->referee ? $request->referee : "";
        $rival_id = $request->rival ? $request->rival : 0;
        $is_home=$request->is_home ? 1 :0;

        if($league_id && $rival_id) {
            $matchadd = new matches();

            $matchadd->league_id = $league_id;
            $matchadd->image = $mainimage;
            $matchadd->date = $date;
            $matchadd->time = $hour . ":" . $minutes;
            $matchadd->location = $location;
            $matchadd->week = $week;
            $matchadd->referee = $referee;
            $matchadd->rival_id = $rival_id;
            $matchadd->is_home = $is_home;
            $matchadd->save();
            $match_id = $matchadd->id;


            $possesion = $request->possesion ? $request->possesion : 0;
            $shot = $request->shot ? $request->shot : 0;
            $ongoal = $request->ongoal ? $request->ongoal : 0;
            $redcard = $request->redcard ? $request->redcard : 0;
            $yellowcard = $request->yellowcard ? $request->yellowcard : 0;
            $offsides = $request->offsides ? $request->offsides : 0;
            $corners = $request->corners ? $request->corners : 0;
            $saves = $request->saves ? $request->saves : 0;
            $score = $request->score ? $request->score : 0;
            $is_neftchi = 1;

            $line_up = "";

            $rowplayerscount = $request->rowplayerscount;
            if ($rowplayerscount) {
                foreach ($rowplayerscount as $key1 => $item) {

                    if ($key1 == 0) $line_up = $line_up . $item;
                    else
                        $line_up = $line_up . '-' . $item;
                }
            }
            $addmatch_results = new match_results();

            $addmatch_results->possesion = $possesion;
            $addmatch_results->shot = $shot;
            $addmatch_results->ongoal = $ongoal;
            $addmatch_results->redcard = $redcard;
            $addmatch_results->yellowcard = $yellowcard;
            $addmatch_results->offsides = $offsides;
            $addmatch_results->corners = $corners;
            $addmatch_results->saves = $saves;
            $addmatch_results->score = $score;
            $addmatch_results->is_neftchi = 1;
            $addmatch_results->match_id = $match_id;
            $addmatch_results->formalization = $line_up;

            $addmatch_results->save();

            $rpossesion = $request->rpossesion ? $request->rpossesion : 0;
            $rshot = $request->rshot ? $request->rshot : 0;
            $rongoal = $request->rongoal ? $request->rongoal : 0;
            $rredcard = $request->rredcard ? $request->rredcard : 0;
            $ryellowcard = $request->ryellowcard ? $request->ryellowcard : 0;
            $roffsides = $request->roffsides ? $request->roffsides : 0;
            $rcorners = $request->rcorners ? $request->rcorners : 0;
            $rsaves = $request->rsaves ? $request->rsaves : 0;
            $rscore = $request->rscore ? $request->rscore : 0;
            $is_neftchi = 0;
            $rivalline_up = "";
            $rowrivalscount = $request->rowrivalscount;
            if ($rowrivalscount) {
                foreach ($rowrivalscount as $key1 => $item) {
                    if ($key1 == 0) $rivalline_up = $rivalline_up . $item;
                    else
                        $rivalline_up = $rivalline_up . '-' . $item;
                }
            }
            $raddmatch_results = new match_results();
            $raddmatch_results->possesion = $rpossesion;
            $raddmatch_results->shot = $rshot;
            $raddmatch_results->ongoal = $rongoal;
            $raddmatch_results->redcard = $rredcard;
            $raddmatch_results->yellowcard = $ryellowcard;
            $raddmatch_results->offsides = $roffsides;
            $raddmatch_results->corners = $rcorners;
            $raddmatch_results->saves = $rsaves;
            $raddmatch_results->score = $rscore;
            $raddmatch_results->is_neftchi = 0;
            $raddmatch_results->match_id = $match_id;
            $raddmatch_results->formalization = $rivalline_up;

            $raddmatch_results->save();

            league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
            update(['p' => DB::raw('p+1'), 'gs' => DB::raw('gs+' . $score), 'ga' => DB::raw('ga+' . $rscore)]);

            league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
            update(['p' => DB::raw('p+1'), 'gs' => DB::raw('gs+' . $rscore), 'ga' => DB::raw('ga+' . $score)]);

            if ($score > $rscore) {

                league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
                update(['pts' => DB::raw('pts+3'), 'w' => DB::raw('w+1')]);

                league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
                update(['l' => DB::raw('l+1')]);
            }

            if ($score < $rscore) {

                league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
                update(['pts' => DB::raw('pts+3'), 'w' => DB::raw('w+1')]);

                league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
                update(['l' => DB::raw('l+1')]);
            }
            if ($score == $rscore) {

                league_result_table::where('league_id', $league_id)->where('rival_id', $rival_id)->
                update(['pts' => DB::raw('pts+1'), 'd' => DB::raw('d+1')]);

                league_result_table::where('league_id', $league_id)->where('rival_id', 0)->
                update(['pts' => DB::raw('pts+1'), 'd' => DB::raw('d+1')]);
            }


            $footballers = $request->footballer;
            $played = $request->played;
            $is_captain = $request->is_captain;


            if ($line_up) {
                foreach ($footballers as $key1 => $array1) {

                    foreach ($array1 as $key2 => $array2) {



                        $playtime = $played[$key1][$key2] ? $played[$key1][$key2] : 0;
                        if(isset($is_captain[$key1][$key2]) && $is_captain[$key1][$key2]==1 ) $captain=1; else $captain=0;
                        $missed_goal=0;
                        $dry_game=0;
                        $subs_off=0;
                        if ($key1 == 0 && $key2 == 0) {

                            $rival_action = $request->rival_action;
                            $rival_action_time = $request->rival_action_time;
                            foreach ($rival_action as $key=>$items)
                            {
                                if($items=='goal' && $rival_action_time[$key] < $playtime){

                                    $missed_goal=$missed_goal+1;
                                }
                            }
                            if($missed_goal==0) $dry_game=1;
                        }


                        $subs_off=0;
                        if($playtime>=90 ) { $subs_off=0;}
                        elseif($playtime>0 && $playtime<90) {$subs_off=1;}

                        $addneftchiteam = new match_footballers_result;

                        $addneftchiteam->match_id = $match_id;
                        $addneftchiteam->footballers_id = $footballers[$key1][$key2];
                        $addneftchiteam->position_row = $key1;
                        $addneftchiteam->position_col = $key2;
                        $addneftchiteam->is_captain = $captain;
                        $addneftchiteam->played = $playtime;
                        $addneftchiteam->starts = 1;
                        $addneftchiteam->subs_off = $subs_off;
                        $addneftchiteam->missed_goal = $missed_goal;
                        $addneftchiteam->dry_game = $dry_game;
                        $addneftchiteam->is_mainfootballer = 1;
                        $addneftchiteam->is_rival = 0;

                        $add_footballer=Footballers::where('id',$footballers[$key1][$key2])->get();
                        $is_footballer=count($add_footballer);
                        if($is_footballer!=0) {

                            if($add_footballer->first()->team_id==$neftchi_team_id->neftchi_team_id) $is_footballer=2;
                        }

                        if ($addneftchiteam->save() && $is_footballer==2 ){


                            /*if ($footballers[$key1][$key2]) Footballers::where('id', $footballers[$key1][$key2])->
                            update(['number_games_neftchi' => DB::raw('number_games_neftchi+1'),
                                'time_field' => DB::raw('time_field+' . $playtime),
                                'time_field_total' => DB::raw('time_field_total+' . $playtime)
                            ]);*/

                            $footballer_id=$footballers[$key1][$key2];
                            $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
                            $league_season=leagues::where('id',$league_id)->first()->start_end;
                            $team_id=Footballers::where('id',$footballer_id)->first()->team_id;

                            $count=FootballerStatistics::where('footballer_id',$footballer_id)->where('league_name_id',$league_name_id)
                                ->where('league_season',$league_season)->get();
                            if(count($count)==1)
                                $edit_statistics=FootballerStatistics::where('footballer_id',$footballer_id)
                                    ->where('league_name_id',$league_name_id)->
                                    where('league_season',$league_season)->update([

                                        'played_neftchi'=>DB::raw('played_neftchi+1'),
                                        'time_field'=>DB::raw('time_field+' . $playtime),
                                        'starts'=>DB::raw('starts+1'),
                                        'subs_off'=>DB::raw('subs_off+' . $subs_off),
                                        'missed_goal'=>DB::raw('missed_goal+' . $missed_goal),
                                        'dry_game'=>DB::raw('dry_game+' . $dry_game)

                                    ]);
                            else
                                $addstatistics=FootballerStatistics::create([

                                    'footballer_id'=>$footballer_id,
                                    'league_name_id'=>$league_name_id,
                                    'league_season'=>$league_season,
                                    'team_id'=>$team_id,
                                    'played_neftchi'=>DB::raw('played_neftchi+1'),
                                    'time_field'=>DB::raw('time_field+' . $playtime),
                                    'starts'=>DB::raw('starts+1'),
                                    'subs_off'=>DB::raw('subs_off+' . $subs_off),
                                    'missed_goal'=>DB::raw('missed_goal+' . $missed_goal),
                                    'dry_game'=>DB::raw('dry_game+' . $dry_game)


                                ]);


                        }

                    }

                }
            }
            $neftchisubteam = $request->neftchisubteam;
            $neftchisubstitude = $request->neftchisubstitude;
            $subplayed = $request->subplayed;
            $substime = $request->substime;


            foreach ($neftchisubteam as $key => $array1) {

                if ($array1) {
                    $played = $subplayed[$key] ? $subplayed[$key] : 0;
                    $addneftchiteam = new match_footballers_result;

                    $addneftchiteam->match_id = $match_id;
                    $addneftchiteam->footballers_id = $neftchisubteam[$key];
                    $addneftchiteam->is_mainfootballer = 0;
                    $addneftchiteam->is_rival = 0;
                    $addneftchiteam->played = $played;
                    $addneftchiteam->substime = $substime[$key];
                    $addneftchiteam->starts = 0;
                    $addneftchiteam->subs_off = 0;
                    $addneftchiteam->missed_goal = 0;
                    $addneftchiteam->dry_game =0;
                    $addneftchiteam->substitudeplayer = $neftchisubstitude[$key];
                    if ($addneftchiteam->save()) {

                        /*Footballers::where('id', $neftchisubteam[$key])->
                        update([
                            'number_games_neftchi' => DB::raw('number_games_neftchi+1'),
                            'time_field' => DB::raw('time_field+' . $played),
                            'time_field_total' => DB::raw('time_field_total+' . $played)
                        ]);*/

                        $footballer_id=$neftchisubteam[$key];
                        $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
                        $league_season=leagues::where('id',$league_id)->first()->start_end;
                        $team_id=Footballers::where('id',$footballer_id)->first()->team_id;

                        $count=FootballerStatistics::where('footballer_id',$footballer_id)->where('league_name_id',$league_name_id)
                            ->where('league_season',$league_season)->get();

                        if (count($count) == 1)
                            $edit_statistics = FootballerStatistics::where('footballer_id', $footballer_id)
                                ->where('league_name_id', $league_name_id)->
                                where('league_season', $league_season)->update([

                                    'played_neftchi' => DB::raw('played_neftchi+1'),
                                    'time_field' => DB::raw('time_field+' . $played),


                                ]);
                        else
                            $addstatistics = FootballerStatistics::create([

                                'footballer_id' => $footballer_id,
                                'league_name_id' => $league_name_id,
                                'league_season' => $league_season,
                                'team_id' => $team_id,
                                'played_neftchi' => DB::raw('played_neftchi+1'),
                                'time_field' => DB::raw('time_field+' . $played),

                            ]);


                    }
                }
            }


            $rfootballers = $request->rfootballer;
            $rplayed = $request->rplayed;
            $ris_captain = $request->ris_captain;

            if ($rivalline_up) {
                foreach ($rfootballers as $key1 => $array1) {

                    foreach ($array1 as $key2 => $array2) {

                        $playtime = $rplayed[$key1][$key2] ? $rplayed[$key1][$key2] : 0;
                        if(isset($ris_captain[$key1][$key2]) && $ris_captain[$key1][$key2]==1 ) $captain=1; else $captain=0;

                        $addneftchiteam = new match_footballers_result;

                        $addneftchiteam->match_id = $match_id;
                        $addneftchiteam->footballers_id = $rfootballers[$key1][$key2];
                        $addneftchiteam->is_captain = $captain;
                        $addneftchiteam->position_row = $key1;
                        $addneftchiteam->position_col = $key2;
                        $addneftchiteam->played = $playtime;
                        $addneftchiteam->is_mainfootballer = 1;
                        $addneftchiteam->is_rival = 1;

                        $addneftchiteam->save();

                    }

                }
            }


            $rivalsubteam = $request->rivalsubteam;
            $rivalsubstitude = $request->rivalsubstitude;
            $rsubplayed = $request->rsubplayed;
            $rsubstime = $request->rsubstime;


            foreach ($rivalsubteam as $key => $array1) {

                if ($array1) {
                    $played = $rsubplayed[$key] ? $rsubplayed[$key] : 0;

                    $addneftchiteam = new match_footballers_result;

                    $addneftchiteam->match_id = $match_id;
                    $addneftchiteam->footballers_id = $rivalsubteam[$key];
                    $addneftchiteam->is_mainfootballer = 0;
                    $addneftchiteam->is_rival = 1;
                    $addneftchiteam->played = $played;
                    $addneftchiteam->substime = $rsubstime[$key];
                    $addneftchiteam->substitudeplayer = $rivalsubstitude[$key];
                    $addneftchiteam->save();
                }
            }

            $neftchi_action = $request->neftchi_action;
            $neftchi_action_footballer = $request->neftchi_action_footballer;
            $neftchi_action_time = $request->neftchi_action_time;

            foreach ($neftchi_action as $key => $array1) {

                if ($array1 && $neftchi_action_footballer[$key] && $neftchi_action_time[$key]) {
                    $addneftchiaction = new match_action_table;

                    $addneftchiaction->match_id = $match_id;
                    $addneftchiaction->footballer_id = $neftchi_action_footballer[$key];
                    $addneftchiaction->action = $array1;
                    $addneftchiaction->is_rival = 0;
                    $addneftchiaction->time = $neftchi_action_time[$key];
                    if ($addneftchiaction->save()) {

                        $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
                        $league_season=leagues::where('id',$league_id)->first()->start_end;
                        $footballer_id=$neftchi_action_footballer[$key];
                        $team_id=Footballers::where('id',$footballer_id)->first()->team_id;
                        $count=FootballerStatistics::where('footballer_id',$footballer_id)->where('league_name_id',$league_name_id)
                            ->where('league_season',$league_season)->get();



                        if ($array1 == "yellow_card")
                        {
                            if (count($count) == 1)

                                $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                    ->where('league_name_id',$league_name_id)->
                                    where('league_season',$league_season)->
                                    update(['yellow_card' => DB::raw('yellow_card+1')]);

                            else
                                $add = FootballerStatistics::create([

                                    'footballer_id' => $footballer_id,
                                    'league_name_id' => $league_name_id,
                                    'league_season' => $league_season,
                                    'team_id' => $team_id,
                                    'yellow_card'=>1

                                ]);

                        }

                        if ($array1 == "red_card")
                        {
                            if (count($count) == 1)

                                $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                    ->where('league_name_id',$league_name_id)->
                                    where('league_season',$league_season)->
                                    update(['red_card' => DB::raw('red_card+1')]);

                            else
                                $add = FootballerStatistics::create([

                                    'footballer_id' => $footballer_id,
                                    'league_name_id' => $league_name_id,
                                    'league_season' => $league_season,
                                    'team_id' => $team_id,
                                    'red_card'=>1

                                ]);

                        }

                        if ($array1 == "goal")
                        {
                            if (count($count) == 1)

                                $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                    ->where('league_name_id',$league_name_id)->
                                    where('league_season',$league_season)->
                                    update(['goals' => DB::raw('goals+1')]);

                            else
                                $add = FootballerStatistics::create([

                                    'footballer_id' => $footballer_id,
                                    'league_name_id' => $league_name_id,
                                    'league_season' => $league_season,
                                    'team_id' => $team_id,
                                    'goals'=>1

                                ]);

                        }

                        if ($array1 == "pen")
                        {
                            if (count($count) == 1)

                                $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                    ->where('league_name_id',$league_name_id)->
                                    where('league_season',$league_season)->
                                    update(['goals' => DB::raw('goals+1')]);

                            else
                                $add = FootballerStatistics::create([

                                    'footballer_id' => $footballer_id,
                                    'league_name_id' => $league_name_id,
                                    'league_season' => $league_season,
                                    'team_id' => $team_id,
                                    'goals'=>1

                                ]);

                        }

                        if ($array1 == "asists")
                        {
                            if (count($count) == 1)

                                $edit=FootballerStatistics::where('footballer_id',$footballer_id)
                                    ->where('league_name_id',$league_name_id)->
                                    where('league_season',$league_season)->
                                    update(['asists' => DB::raw('asists+1')]);

                            else
                                $add = FootballerStatistics::create([

                                    'footballer_id' => $footballer_id,
                                    'league_name_id' => $league_name_id,
                                    'league_season' => $league_season,
                                    'team_id' => $team_id,
                                    'asists'=>1

                                ]);

                        }


                    }
                }
            }


            $rival_action = $request->rival_action;
            $rival_action_footballer = $request->rival_action_footballer;
            $rival_action_time = $request->rival_action_time;

            foreach ($rival_action as $key => $array1) {

                if ($array1 && $rival_action_footballer[$key] && $rival_action_time[$key]) {
                    $addrivalaction = new match_action_table;

                    $addrivalaction->match_id = $match_id;
                    $addrivalaction->footballer_id = $rival_action_footballer[$key];
                    $addrivalaction->action = $array1;
                    $addrivalaction->is_rival = 1;
                    $addrivalaction->time = $rival_action_time[$key];
                    $addrivalaction->save();

                }
            }
        }

        return redirect("/adminpanel/matches");


    }

    public function post_editmatchplayers($id, Request $request)
    {
        $league_id=matches::where('id',$id)->first()->league_id;
        $league_name_id=leagues::where('id',$league_id)->first()->league_name_id;
        $league_season=leagues::where('id',$league_id)->first()->start_end;
        $neftchi_team_id = leagues::find($league_id);

        $match_id=$id;
        $neftchi_line_up=match_results::where('match_id', $id)->where('is_neftchi', 1)->first();
        $rival_line_up=match_results::where('match_id', $id)->where('is_neftchi', 0)->first();
        $rscore=$rival_line_up->score;
        $saves=$neftchi_line_up->saves;
        if($neftchi_line_up->formalization)
        {
            $footballers = $request->footballer;
            $played = $request->played;
            $is_captain = $request->is_captain;

            foreach ($footballers as $key1 => $array1) {

                foreach ($array1 as $key2 => $array2) {


                    $playtime = $played[$key1][$key2] ? $played[$key1][$key2] : 0;
                    if(isset($is_captain[$key1][$key2]) && $is_captain[$key1][$key2]==1 ) $captain=1; else $captain=0;
                    $missed_goal=0;
                    $dry_game=0;
                    $subs_off=0;
                    if ($key1 == 0 && $key2 == 0) {

                        $rival_actions =match_action_table::where('match_id',$id)->where('is_rival',0)->get();
                        foreach ($rival_actions as $key=>$items)
                        {
                            if($items->action=='goal' && $items->time < $playtime){

                                $missed_goal=$missed_goal+1;
                            }
                        }
                        if($missed_goal==0) $dry_game=1;
                    }



                    if($playtime>=90) { $subs_off=0;}
                    elseif($playtime>0 && $playtime<90) {$subs_off=1;}

                    $data = match_footballers_result::where('match_id', $id)->where('position_row', $key1)->
                    where('position_col', $key2)->where('is_mainfootballer', 1)->where('is_rival', 0)->first();

                    if($data)
                    {
                        Footballers::where('id', $data->footballers_id)->
                        update(['number_games_neftchi' => DB::raw('number_games_neftchi-1'),
                            'time_field' => DB::raw('time_field-' . $data->played),
                            'time_field_total' => DB::raw('time_field_total-' . $data->played)
                        ]);

                        FootballerStatistics::where('footballer_id', $data->footballers_id)
                            ->where('league_name_id',$league_name_id)->
                            where('league_season',$league_season)->update([

                                'played_neftchi'=>DB::raw('played_neftchi-1'),
                                'time_field'=>DB::raw('time_field-' . $data->played),
                                'starts'=>DB::raw('starts-1'),
                                'subs_off'=>DB::raw('subs_off-' . $data->subs_off),
                                'missed_goal'=>DB::raw('missed_goal-' . $data->missed_goal),
                                'dry_game'=>DB::raw('dry_game-' . $data->dry_game)

                            ]);
                    }

                    match_footballers_result::where('match_id', $id)->where('position_row', $key1)->
                    where('position_col', $key2)->where('is_mainfootballer', 1)->where('is_rival', 0)->
                    update([

                        'footballers_id' => $footballers[$key1][$key2],
                        'played' => $playtime,
                        'is_captain'=>$captain
                    ]);


                    if ($footballers[$key1][$key2]) {

                        Footballers::where('id', $footballers[$key1][$key2])->
                        update(['number_games_neftchi' => DB::raw('number_games_neftchi+1'),
                            'time_field' => DB::raw('time_field+' . $playtime),
                            'time_field_total' => DB::raw('time_field_total+' . $playtime)
                        ]);

                        $player_statistics=FootballerStatistics::where('footballer_id',$footballers[$key1][$key2])
                            ->where('league_name_id',$league_name_id)->
                            where('league_season',$league_season)->get();

                        if(count($player_statistics)>0)

                        {
                            // return $footballers[$key1][$key2];
                            $edit=FootballerStatistics::where('footballer_id',$footballers[$key1][$key2])
                                ->where('league_name_id',$league_name_id)->
                                where('league_season',$league_season)->update([

                                    'team_id'=>Footballers::find($footballers[$key1][$key2])->team_id,
                                    'played_neftchi'=>DB::raw('played_neftchi+1'),
                                    'time_field'=>DB::raw('time_field+' . $playtime),
                                    'starts'=>DB::raw('starts+1'),
                                    'subs_off'=>DB::raw('subs_off+' . $subs_off),
                                    'missed_goal'=>DB::raw('missed_goal+' . $missed_goal),
                                    'dry_game'=>DB::raw('dry_game+' . $dry_game)

                                ]);


                        }
                        else
                            $edit=FootballerStatistics::create([
                                'footballer_id'=>$footballers[$key1][$key2],
                                'league_season'=>$league_season,
                                'league_name_id'=>$league_name_id,
                                'team_id'=>Footballers::find($footballers[$key1][$key2])->team_id,
                                'played_neftchi'=>1,
                                'time_field'=>$playtime,
                                'starts'=>1,
                                'subs_off'=>$subs_off,
                                'missed_goal'=>$missed_goal,
                                'dry_game'=> $dry_game

                            ]);




                    }

                }

            }
        }
        else{
            match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 1)->
            where('is_rival', 0)->delete();
            $line_up = "";

            $rowplayerscount = $request->rowplayerscount;
            if($rowplayerscount){
                foreach ($rowplayerscount as $key1 => $item) {

                    if ($key1 == 0) $line_up = $line_up . $item;
                    else
                        $line_up = $line_up . '-' . $item;
                }}
            $match_results_edit = match_results::where('match_id', $id)->where('is_neftchi', 1)->update(['formalization' => $line_up]);
            if($match_results_edit)  {

                $footballers = $request->footballer;
                $played = $request->played;
                $is_captain = $request->is_captain;

                if($line_up)

                {

                    foreach ($footballers as $key1 => $array1) {

                        foreach ($array1 as $key2 => $array2) {



                            $playtime = $played[$key1][$key2] ? $played[$key1][$key2] : 0;
                            if(isset($is_captain[$key1][$key2]) && $is_captain[$key1][$key2]==1 ) $captain=1; else $captain=0;
                            $missed_goal=0;
                            $dry_game=0;
                            $subs_off=0;
                            if ($key1 == 0 && $key2 == 0) {

                                $rival_actions =match_action_table::where('match_id',$id)->where('is_rival',0)->get();
                                foreach ($rival_actions as $key=>$items)
                                {
                                    if($items->action=='goal' && $items->time < $playtime){

                                        $missed_goal=$missed_goal+1;
                                    }
                                }
                                if($missed_goal==0) $dry_game=1;
                            }



                            if($playtime>=90) { $subs_off=0;}
                            elseif($playtime>0 && $playtime<90) {$subs_off=1;}

                            $addneftchiteam = new match_footballers_result;

                            $addneftchiteam->match_id = $match_id;
                            $addneftchiteam->footballers_id = $footballers[$key1][$key2];
                            $addneftchiteam->position_row = $key1;
                            $addneftchiteam->position_col = $key2;
                            $addneftchiteam->played = $playtime;
                            $addneftchiteam->is_captain = $captain;
                            $addneftchiteam->starts = 1;
                            $addneftchiteam->subs_off = $subs_off;
                            $addneftchiteam->missed_goal = $missed_goal;
                            $addneftchiteam->dry_game = $dry_game;
                            $addneftchiteam->is_mainfootballer = 1;
                            $addneftchiteam->is_rival = 0;

                            $add_footballer=Footballers::where('id',$footballers[$key1][$key2])->get();
                            $is_footballer=count($add_footballer);
                            if($is_footballer!=0) {

                                if($add_footballer->first()->team_id==$neftchi_team_id->neftchi_team_id) $is_footballer=2;
                            }

                            if ($addneftchiteam->save()  && $is_footballer==2 ) {


                                /*if ($footballers[$key1][$key2]) Footballers::where('id', $footballers[$key1][$key2])->
                                update(['number_games_neftchi' => DB::raw('number_games_neftchi+1'),
                                    'time_field' => DB::raw('time_field+' . $playtime),
                                    'time_field_total' => DB::raw('time_field_total+' . $playtime)
                                ]);*/

                                $footballer_id = $footballers[$key1][$key2];

                                $team_id = Footballers::where('id', $footballer_id)->first()->team_id;

                                $count = FootballerStatistics::where('footballer_id', $footballer_id)->where('league_name_id', $league_name_id)
                                    ->where('league_season', $league_season)->get();
                                if (count($count) == 1)
                                    $edit_statistics = FootballerStatistics::where('footballer_id', $footballer_id)
                                        ->where('league_name_id', $league_name_id)->
                                        where('league_season', $league_season)->update([

                                            'played_neftchi' => DB::raw('played_neftchi+1'),
                                            'time_field' => DB::raw('time_field+' . $playtime),
                                            'starts' => DB::raw('starts+1'),
                                            'subs_off' => DB::raw('subs_off+' . $subs_off),
                                            'missed_goal' => DB::raw('missed_goal+' . $missed_goal),
                                            'dry_game' => DB::raw('dry_game+' . $dry_game)

                                        ]);
                                else
                                    $addstatistics = FootballerStatistics::create([

                                        'footballer_id' => $footballer_id,
                                        'league_name_id' => $league_name_id,
                                        'league_season' => $league_season,
                                        'team_id' => $team_id,
                                        'played_neftchi' => 1,
                                        'time_field' => $playtime,
                                        'starts' => 1,
                                        'subs_off' => $subs_off,
                                        'missed_goal' => $missed_goal,
                                        'dry_game' => $dry_game


                                    ]);

                            }
                        }

                    }}

            }

        }

        $subteam = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 0)->where('is_rival', 0)->get();

        foreach ($subteam as $items) {
            $played=$items->played ? $items->played : 0;
            Footballers::where('id', $items->footballers_id)->
            update(['number_games_neftchi' => DB::raw('number_games_neftchi-1'),
                'time_field' => DB::raw('time_field-' . $played),
                'time_field_total' => DB::raw('time_field_total-' . $played)
            ]);
            FootballerStatistics::where('footballer_id',$items->footballers_id)
                ->where('league_name_id',$league_name_id)->
                where('league_season',$league_season)->update([

                    'played_neftchi'=>DB::raw('played_neftchi-1'),
                    'time_field'=>DB::raw('time_field-' . $played)

                ]);

        }
        $subteam = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 0)->where('is_rival', 0)->delete();

        $neftchisubteam = $request->neftchisubteam;
        $neftchisubstitude = $request->neftchisubstitude;
        $subplayed = $request->subplayed;
        $substime = $request->substime;


        foreach ($neftchisubteam as $key => $array1) {

            if ($array1) {
                $played = $subplayed[$key] ? $subplayed[$key] : 0;
                $addneftchiteam = new match_footballers_result;

                $addneftchiteam->match_id = $match_id;
                $addneftchiteam->footballers_id = $neftchisubteam[$key];
                $addneftchiteam->is_mainfootballer = 0;
                $addneftchiteam->is_rival = 0;
                $addneftchiteam->played = $played;
                $addneftchiteam->substime = $substime[$key];
                $addneftchiteam->starts = 0;
                $addneftchiteam->subs_off = 0;
                $addneftchiteam->missed_goal = 0;
                $addneftchiteam->dry_game =0;
                $addneftchiteam->substitudeplayer = $neftchisubstitude[$key];
                if ($addneftchiteam->save()) {

                    /*Footballers::where('id', $neftchisubteam[$key])->
                    update([
                        'number_games_neftchi' => DB::raw('number_games_neftchi+1'),
                        'time_field' => DB::raw('time_field+' . $played),
                        'time_field_total' => DB::raw('time_field_total+' . $played)
                    ]);*/

                    $footballer_id=$neftchisubteam[$key];
                    $team_id=Footballers::where('id',$footballer_id)->first()->team_id;

                    $count=FootballerStatistics::where('footballer_id',$footballer_id)->where('league_name_id',$league_name_id)
                        ->where('league_season',$league_season)->get();

                    if (count($count) == 1)
                        $edit_statistics = FootballerStatistics::where('footballer_id', $footballer_id)
                            ->where('league_name_id', $league_name_id)->
                            where('league_season', $league_season)->update([

                                'played_neftchi' => DB::raw('played_neftchi+1'),
                                'time_field' => DB::raw('time_field+' . $played),


                            ]);
                    else
                        $addstatistics = FootballerStatistics::create([

                            'footballer_id' => $footballer_id,
                            'league_name_id' => $league_name_id,
                            'league_season' => $league_season,
                            'team_id' => $team_id,
                            'played_neftchi' => 1,
                            'time_field' => $played

                        ]);


                }
            }
        }


        if($rival_line_up->formalization)
        {
            $rfootballers = $request->rfootballer;
            $rplayed = $request->rplayed;
            $ris_captain = $request->ris_captain;

            $oldrivalteam = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 1)->where('is_rival', 1)
                ->delete();

            foreach ($rfootballers as $key1 => $array1) {

                foreach ($array1 as $key2 => $array2) {



                    if(isset($ris_captain[$key1][$key2]) && $ris_captain[$key1][$key2]==1 ) $captain=1; else $captain=0;

                    $addneftchiteam = new match_footballers_result;

                    $addneftchiteam->match_id = $id;
                    $addneftchiteam->footballers_id = $rfootballers[$key1][$key2];
                    $addneftchiteam->position_row = $key1;
                    $addneftchiteam->position_col = $key2;
                    $addneftchiteam->is_captain = $captain;
                    $addneftchiteam->played = $rplayed[$key1][$key2];
                    $addneftchiteam->is_mainfootballer = 1;
                    $addneftchiteam->is_rival = 1;

                    $addneftchiteam->save();

                }

            }
        }
        else{
            match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 1)->
            where('is_rival', 1)->delete();
            $rivalline_up = "";
            $rowrivalscount = $request->rowrivalscount;
            if($rowrivalscount){
                foreach ($rowrivalscount as $key1 => $item) {
                    if ($key1 == 0) $rivalline_up = $rivalline_up . $item;
                    else
                        $rivalline_up = $rivalline_up . '-' . $item;
                }
            }

            $match_results_edit = match_results::where('match_id', $id)->where('is_neftchi', 0)
                ->update(['formalization' => $rivalline_up]);
            if($match_results_edit){

                $rfootballers = $request->rfootballer;
                $rplayed = $request->rplayed;
                $ris_captain = $request->ris_captain;

                if($rivalline_up)
                {foreach ($rfootballers as $key1 => $array1) {

                    foreach ($array1 as $key2 => $array2) {

                        $playtime = $rplayed[$key1][$key2] ? $rplayed[$key1][$key2] : 0;
                        if(isset($ris_captain[$key1][$key2]) && $ris_captain[$key1][$key2]==1 ) $captain=1; else $captain=0;

                        $addneftchiteam = new match_footballers_result;

                        $addneftchiteam->match_id = $match_id;
                        $addneftchiteam->footballers_id = $rfootballers[$key1][$key2];
                        $addneftchiteam->position_row = $key1;
                        $addneftchiteam->position_col = $key2;
                        $addneftchiteam->played = $playtime;
                        $addneftchiteam->is_captain = $captain;
                        $addneftchiteam->is_mainfootballer = 1;
                        $addneftchiteam->is_rival = 1;

                        $addneftchiteam->save();

                    }

                }}
            }


        }
        $rivalsubteam = $request->rivalsubteam;
        $rivalsubstitude = $request->rivalsubstitude;
        $rsubplayed = $request->rsubplayed;
        $rsubstime = $request->rsubstime;

        if ($rivalsubteam) {
            $oldrivalsubteam = match_footballers_result::where('match_id', $id)->where('is_mainfootballer', 0)->where('is_rival', 1)
                ->delete();

            foreach ($rivalsubteam as $key => $array1) {

                if ($array1) {
                    $played=$rsubplayed[$key] ? $rsubplayed[$key] : 0;
                    $addneftchiteam = new match_footballers_result;

                    $addneftchiteam->match_id = $id;
                    $addneftchiteam->footballers_id = $rivalsubteam[$key];
                    $addneftchiteam->is_mainfootballer = 0;
                    $addneftchiteam->is_rival = 1;
                    $addneftchiteam->played = $played;
                    $addneftchiteam->substime = $rsubstime[$key];
                    $addneftchiteam->substitudeplayer = $rivalsubstitude[$key];
                    $addneftchiteam->save();
                }
            }

        }
        return redirect('adminpanel/matches');

    }

    public function get_matchgallery($id)
    {
        $match_gallery=match_gallery::where('match_id',$id)->get();
        $match_videos=match_videos::where('match_id',$id)->get();
        $match=matches::find($id);
        return view('admin.matchgallery',compact('match_videos','match_gallery','match'));


    }
    public function post_matchgallery(Request $request,$id)
    {
        $data=matches::find($id);

        $mainimage=$data->image;
        $file_main=$request->file('image');

        if($file_main)
        {
            $file_name=$file_main->getClientOriginalName();
            $file_extension=$file_main->getClientOriginalExtension();
            $type = ["png","jpg","gif","bmp","jpeg","webp","svg"];
            if(in_array($file_extension,$type))
            {
                $myfile =  $file_name;
                $image= str_slug('match-'.$data->id.rand(99999,1000000).Carbon::now()).'.'.$file_extension;

                if( $file_main->move('uploads/awards',$image)) $mainimage=$image;
                else $mainimage=$data->image;
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
                    $otherfile = str_slug('match-'.$data->id.rand(99999,1000000).Carbon::now()).'.'.$extension;

                    $add_gallery_image=new match_gallery;
                    $add_gallery_image->image=$otherfile;
                    $add_gallery_image->match_id=$id;


                    if($add_gallery_image->save()) $new->move('uploads/awards',$otherfile);

                }
            }
        }


        $video=$request->video_url;

        if($video>0)
        {

            $videos=match_videos::where('match_id',$id)->delete();

            foreach ($video as $key => $items) {
                if($items)
                {
                    $add_video=new match_videos;
                    $add_video->url=$items;
                    $add_video->match_id=$id;

                    $add_video->save();
                }
            }

        }

        $data->image=$mainimage;

        if($data->save()) return redirect('/adminpanel/matchgallery/'.$id);





    }

    public function get_delmatchgallery($id){

        $photos=match_gallery::find($id);
        $image=$photos->image;
        $photos=$photos->delete();
        if($photos)
        {

            $image_path = "uploads/awards/".$image;
            File::delete($image_path);

            return redirect()->back();;
        }

    }
    public function get_commenttypes(){

        $data=comment_types::all();
        return view('admin.comment_types',compact('data'));
    }

    public function  get_matchcomments($id){
        $types=comment_types::all();
        $data=DB::table('match_comments')->where('match_id',$id)->
        join('comment_types', 'comment_types.id', '=', 'match_comments.type_id')
            ->select('match_comments.*', 'comment_types.icon', 'comment_types.header_az')->get();
        return view('admin.addmatchcomments',compact('data','types'));
    }
    public function  post_commenttypes(Request $request){

        $file = $request->file('icon');

        if ($file) {
            $file_name = $file->getClientOriginalName();
            $file_extension = $file->getClientOriginalExtension();
            $type = ["png", "jpg", "gif", "bmp", "jpeg","webp","svg"];
            if (in_array($file_extension, $type)) {
                $myfile = str_slug($file->getClientOriginalName() . '-'.Carbon::now()) . '.' . $file->getClientOriginalExtension();
            }
            $file = $file->move('uploads/awards',$myfile);
            if ($file){
                $insert  = comment_types::create([

                    'icon' => $myfile,
                    'header_az' => $request->get('header_az'),
                    'header_en' => $request->get('header_en'),
                    'header_ru' => $request->get('header_ru'),


                ]);
            }
            if ($insert){
                return $this->get_commenttypes();
            }
        }
        return 'ERROR';

    }
    public function editcommenttypes(Request $request){

        $file = $request->file('icon');

        $comment = comment_types::where('id',$request->id)->first();
        //$match_id=$comment->match_id;
        if ($file){
            $file_name = $file->getClientOriginalName();
            $file_extension = $file->getClientOriginalExtension();
            $type = ["png", "jpg", "gif", "bmp", "jpeg","webp"];
            if (in_array($file_extension, $type)) {
                $myfile =str_slug($file->getClientOriginalName() . '-'.Carbon::now()) . '.' . $file->getClientOriginalExtension();
            }
            if($file->move('uploads/awards',$myfile))
            { $a=comment_types::where('id',$request->get('id'))->update(['icon' => $myfile]);

                unlink('uploads/awards/'.$comment->icon);
            }
        }
        comment_types::where('id',$request->get('id'))->update([

            'header_az' => $request->get('header_az'),
            'header_en' => $request->get('header_en'),
            'header_ru' => $request->get('header_ru'),

        ]);
        return redirect('/adminpanel/commenttypes/');

    }
    public function delete_commenttype($id){

        $comment=comment_types::find($id);

        $image=$comment->icon;

        $data=$comment->delete();

        if($data)  unlink('uploads/awards/'.$image);

        return redirect('/adminpanel/commenttypes/');

    }

    public function  post_matchcomments($id,Request $request)
    {


        if ($request->type_id && $request->comment_az)
        {
            $insert = match_comments::create([
                'match_id' => $id,
                'type_id' => $request->get('type_id'),
                'time' => $request->get('time'),
                'comment_az' => $request->get('comment_az'),
                'comment_en' => $request->get('comment_en'),
                'comment_ru' => $request->get('comment_ru')

            ]);

            if ($insert) {
                return $this->get_matchcomments($id);
            }
        }
        else  return $this->get_matchcomments($id);

    }

    public function editcomments(Request $request){


        $comment = match_comments::where('id',$request->id)->first();
        $match_id=$comment->match_id;

        match_comments::where('id',$request->get('id'))->update([

            'time' => $request->get('time'),
            'comment_az' => $request->get('comment_az'),
            'comment_en' => $request->get('comment_en'),
            'comment_ru' => $request->get('comment_ru')
        ]);
        return redirect('/adminpanel/matchcomments/'.$match_id);

    }

    public function delete_comments($id){

        $comment=match_comments::find($id);

        $data=$comment->delete();

        if($data)

            return redirect('/adminpanel/matchcomments/'.$comment->match_id);

    }
}
