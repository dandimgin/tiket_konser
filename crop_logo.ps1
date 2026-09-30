Add-Type -AssemblyName System.Drawing
$imgPath = (Resolve-Path 'public/images/logo.png').Path
$img = [System.Drawing.Bitmap]::FromFile($imgPath)
$w = $img.Width
$h = $img.Height
$minX = $w
$minY = $h
$maxX = 0
$maxY = 0

for ($x = 0; $x -lt $w; $x += 2) {
    for ($y = 0; $y -lt $h; $y += 2) {
        $c = $img.GetPixel($x, $y)
        if ($c.R -lt 240 -or $c.G -lt 240 -or $c.B -lt 240) {
            if ($x -lt $minX) { $minX = $x }
            if ($x -gt $maxX) { $maxX = $x }
            if ($y -lt $minY) { $minY = $y }
            if ($y -gt $maxY) { $maxY = $y }
        }
    }
}

$minX = [Math]::Max(0, $minX - 6)
$minY = [Math]::Max(0, $minY - 6)
$maxX = [Math]::Min($w - 1, $maxX + 6)
$maxY = [Math]::Min($h - 1, $maxY + 6)
$cropW = $maxX - $minX + 1
$cropH = $maxY - $minY + 1

$cropped = New-Object System.Drawing.Bitmap $cropW, $cropH, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb
$g = [System.Drawing.Graphics]::FromImage($cropped)
$rectDest = New-Object System.Drawing.Rectangle 0, 0, $cropW, $cropH
$rectSrc = New-Object System.Drawing.Rectangle $minX, $minY, $cropW, $cropH
$g.DrawImage($img, $rectDest, $rectSrc, [System.Drawing.GraphicsUnit]::Pixel)
$g.Dispose()

# Make white pixels transparent
for ($x = 0; $x -lt $cropW; $x++) {
    for ($y = 0; $y -lt $cropH; $y++) {
        $c = $cropped.GetPixel($x, $y)
        if ($c.R -gt 245 -and $c.G -gt 245 -and $c.B -gt 245) {
            $cropped.SetPixel($x, $y, [System.Drawing.Color]::FromArgb(0, 255, 255, 255))
        }
    }
}

$outPath = (Join-Path (Resolve-Path 'public/images').Path 'tiketin-logo.png')
$cropped.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)
$cropped.Dispose()
$img.Dispose()
Write-Output "Successfully saved cropped transparent logo to $outPath"
