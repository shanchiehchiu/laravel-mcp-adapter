<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 範例資料模型，只是為了讓 ExampleMcpTools::listExampleItems() 有真正的
 * 資料庫可以查，示範「Tools 直接呼叫 Eloquent」這個架構選擇（見
 * references/writing-tools.md 架構選擇 1）。接上真正資料時砍掉，換成你
 * 自己的 Model。
 */
class ExampleItem extends Model
{
    protected $fillable = ['title', 'status'];
}
