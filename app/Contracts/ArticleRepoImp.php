<?php

namespace App\Contracts;

interface ArticleRepoImp
{
    public function get($id);
    public function removeBy($id);
}
