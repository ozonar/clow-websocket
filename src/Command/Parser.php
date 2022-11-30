<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPHtmlParser\Dom;

class Parser extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:parser';

    protected function configure()
    {
    }

    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello');

        return 1;
    }

    protected function parser($source)
    {
        $dom = new Dom;
        $dom->loadFromFile(dirname(__DIR__) . '/..' . $source);
        echo '|LOADED';

        /** @var Dom\AbstractNode[] $frames */
        $frames = $dom->find('h1');

        $allHeaders = [];

        /** @var Dom\AbstractNode $frame */
        foreach ($frames as $key => $frame) {
            $id = $frame->text;
            $allHeaders[] = trim($id);
        }

        /** @var Dom\AbstractNode[] $frames */
        $frames = $dom->find('div');
        $currentHeader = 0;
        $dataArray = [];
        $introduction = [];

        foreach ($frames as $key => $frame) {

            $text = $frame->innerHtml();

            $text = str_replace("<a", "<span", $text);
            $text = str_replace("</a", "</span", $text);

            $header = $allHeaders[$currentHeader];


            $answers = $frame->find('a');

            $buttons = [];

            foreach ($answers as $answer) {
                $id = $answer->getAttribute('href');
                $id = preg_replace("/[^0-9]/", '', $id);
                $data = $answer->text;

                $buttons[$id] = $data;
            }

            if (strlen($header) < 4) {
                $dataArray[$header] = [
                    'text' => $text,
                    'buttons' => $buttons
                ];
            } else {
                $introduction[] = [
                    'text' => $text,
                    'buttons' => $buttons
                ];
            }

            $currentHeader++;
        }


        // Set introduction
        $finalIntroduction = [];
        $introductionCount = -count($introduction);
        foreach ($introduction as $item) {
            $finalIntroduction[$introductionCount] = $item;
            $introductionCount++;
        }


        // Connect introduction to main array
        foreach ($finalIntroduction as $key => $item) {
            $dataArray[$key] = $item;
        }


        return $dataArray;
    }

    protected function savePageToDb($text, $buttons, $gameId, $frameId, $playerText = '')
    {
        $entityManager = $this->container->get('doctrine')->getManager();

        $page = new \App\Entity\FramePages();

        $page->setFrameId($frameId);
        $page->setText($text);
        $page->setButtons($buttons);
        $page->setPlayerName($playerText);
        $page->setPlayerName($playerText);
        $page->setGame($gameId);

        $entityManager->persist($page);
        $entityManager->flush();
    }
}