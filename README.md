CopyToPlugin
============

This is a Cake Shell class to copy the files for plugin from the baked files.


プラグインを作成するときに、アプリケーションでベイクしたファイル群を丸ごとコピーする、
そんなシェルです。

たとえば "Post" モデルをターゲットにした場合、次のファイルがコピーの対象になります。

1. Model/Post.php
2. Controller/PostsController.php
3. View/Posts/*
4. Test/Case/Model/PostTest.php
5. Test/Case/Controller/PostsControllerTest.php
6. Test/Fixture/PostFixture.php


本当は、ベイクするときにプラグイン用のファイルをプラグインディレクトリに作成出来ればいいんですが、
ベイク関連のカスタマイズが上手く行かなかったので苦肉の策です。

コピーしたファイルの内容は一切変更していないので、そのままではプラグインとしては上手く動きませんよ。

テストは書いてない。 (´・ω・`)

使い方
======

コンソールからこのシェルを呼び出して、あとはパラメータを入力していって下さい。

プラグインのコンソールなので次のようなコマンドになります。

$Console/cake CopyToPlugin.CopyToPlugin

パラメータは次のようなものがあります。

1. モデル名
2. 上書きするか
3. 元ファイルを消すか
4. 元ファイルを消すときにバックアップを取るか

※内部的には個別のファイルごとに上書きやバックアップの設定ができるようにしてるのですが、対応したAPIを実装していないという。。。


テスト書いてない
