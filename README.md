CopyToPlugin
============

This is a Cake Shell class to copy the files for plugin from the baked files on CakePHP framework.

http://blog.xao.jp/blog/cakephp/plugin-to-copy-files-into-plugins-directory/

CakePHP でプラグインを作成するときに、アプリケーションでベイクしたファイル群を丸ごとコピーする、
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

継承元だとか、ロードするクラス名だとか、フィクスチャの名前だとか、
そういったパッケージ名的な要素は出来る範囲でプラグイン空間に収めるようにしています。
つまり、プラグイン記法に変更できるものはかたっぱしから変更しています。
依存関係にあるモデルも全て、同じプラグインにあるものとして書き換えを行なっています。


テストはファイルの移動のロジックしか書いてないです。 (´・ω・`)

使い方
======

コンソールからこのシェルを呼び出して、あとはパラメータを入力していって下さい。

プラグインのコンソールなので次のようなコマンドになります。

$Console/cake CopyToPluginShell.CopyToPlugin

パラメータは次のようなものがあります。

1. モデル名
2. 上書きするか
3. 元ファイルを消すか
4. 元ファイルを消すときにバックアップを取るか

※内部的には個別のファイルごとに上書きやバックアップの設定ができるようにしてるのですが、対応したAPIを実装していないという。。。
