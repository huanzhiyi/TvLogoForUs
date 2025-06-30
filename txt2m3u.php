<?php

$inputFile = 'tv/live.txt';
$outputFile = 'tv/live.m3u';

$lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines === false) {
    die("Error reading input file: " . $inputFile);
}

$channelNameAliasesFile = 'tv/channel_name_alias.json';
$channelNameAliasesContent = file_get_contents($channelNameAliasesFile);
$channelNameAliases = json_decode($channelNameAliasesContent, true);

if ($channelNameAliases === null) {
    die("Error decoding channel name aliases file: " . $channelNameAliasesFile);
}

$m3uContent = "#EXTM3U\n";

foreach ($lines as $line) {
    if (strpos($line, ',#genre#') !== false) {
        $groupTitle = trim(str_replace(',#genre#', '', $line));
    } else {
        $parts = explode(',', $line, 2);

        if (count($parts) == 2) {
            $name = trim($parts[0]);
            $url = trim($parts[1]);

            if (isset($channelNameAliases['__suffix'])) {
                foreach ($channelNameAliases['__suffix'] as $suffix) {
                    if ($suffix !== '高清' && $suffix !== '超高清') {
                        $name = str_replace($suffix, '', $name);
                    }
                }
            }

            $logoName = $name;
            $originalName = trim($parts[0]);

            if (isset($channelNameAliases['__suffix'])) {
                foreach ($channelNameAliases['__suffix'] as $suffix) {
                    if ($suffix !== '高清' && $suffix !== '超高清') {
                        $name = str_replace($suffix, '', $name);
                    }
                    $logoName = str_replace($suffix, '', $logoName);
                }
            }
            $foundAlias = false;

            foreach ($channelNameAliases as $alias => $names) {
                if ($alias === '__suffix') continue;
                if (in_array($name, $names)) {
                    $logoName = $alias;
                    $foundAlias = true;
                    break;
                }
            }
            if (!$foundAlias) {
                foreach ($channelNameAliases as $alias => $names) {
                    if ($alias === '__suffix') continue;
                    if (in_array($originalName, $names)) {
                        $logoName = $alias;
                        break;
                    }
                }
            }

            $logo = "https://raw.githubusercontent.com/huanzhiyi/TvLogoForUs/refs/heads/main/img/" . str_replace(' ', '', $logoName) . ".png";
            $catchup = (stripos($url, '/PLTV/') !== false || stripos($url, '/TVOD/') !== false) ?
                ' catchup="append" catchup-source="?playseek=${(b)yyyyMMddHHmmss}-${(e)yyyyMMddHHmmss}"' : '';

            $displayName = $originalName;
            if (isset($channelNameAliases['__suffix'])) {
                foreach ($channelNameAliases['__suffix'] as $suffix) {
                    if ($suffix !== '高清' && $suffix !== '超高清') {
                        $displayName = str_replace($suffix, '', $displayName);
                    }
                }
            }

            $m3uContent .= "#EXTINF:-1 tvg-name=\"" . $name . "\" tvg-logo=\"" . $logo . "\" group-title=\"" . $groupTitle . "\"" . $catchup . "," . trim($displayName) . "\n";
            $m3uContent .= $url . "\n";
        } else {
            // Log or handle invalid lines
            error_log("Invalid line: " . $line);
        }
    }
}

if (file_put_contents($outputFile, $m3uContent) === false) {
    die("Error writing to output file: " . $outputFile);
}

echo "M3U file generated successfully: " . $outputFile . "\n";

?>
