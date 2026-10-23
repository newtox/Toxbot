import 'dart:io';
import 'dart:math';
import 'dart:typed_data';

const _lowercase = 'abcdefghijkmnopqrstuvwxyz';

const _uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

const _digits = '23456789';

const _symbols = '!#\$%&*+-=?@^_~';

const _pngSignature = [137, 80, 78, 71, 13, 10, 26, 10];

final _random = Random.secure();

final _crcTable = List<int>.generate(256, (n) {
  var c = n;
  for (var k = 0; k < 8; k++) {
    c = (c & 1) == 1 ? 0xedb88320 ^ (c >> 1) : c >> 1;
  }
  return c;
});

String _pick(String characters) =>
    characters[_random.nextInt(characters.length)];

int _crc32(List<int> bytes) {
  var crc = 0xffffffff;
  for (final byte in bytes) {
    crc = _crcTable[(crc ^ byte) & 0xff] ^ (crc >> 8);
  }
  return crc ^ 0xffffffff;
}

List<int> _uint32(int value) => [
      (value >> 24) & 0xff,
      (value >> 16) & 0xff,
      (value >> 8) & 0xff,
      value & 0xff,
    ];

List<int> _pngChunk(String type, List<int> data) {
  final body = [...type.codeUnits, ...data];
  return [..._uint32(data.length), ...body, ..._uint32(_crc32(body))];
}

String generatePassword(int length, {bool symbols = true}) {
  final groups = [_lowercase, _uppercase, _digits, if (symbols) _symbols];
  final all = groups.join();
  final characters = [
    for (final group in groups) _pick(group),
    for (var i = groups.length; i < length; i++) _pick(all),
  ]..shuffle(_random);
  return characters.take(length).join();
}

String generateHex(int bytes) => [
      for (var i = 0; i < bytes; i++)
        _random.nextInt(256).toRadixString(16).padLeft(2, '0'),
    ].join();

int randomColor() => _random.nextInt(0x1000000);

String colorHex(int color) => '#${color.toRadixString(16).padLeft(6, '0')}';

(int, int, int) colorRgb(int color) =>
    ((color >> 16) & 0xff, (color >> 8) & 0xff, color & 0xff);

(int, int, int) colorHsl(int color) {
  final (r, g, b) = colorRgb(color);
  final red = r / 255, green = g / 255, blue = b / 255;
  final high = max(red, max(green, blue));
  final low = min(red, min(green, blue));
  final lightness = (high + low) / 2;
  final delta = high - low;

  if (delta == 0) {
    return (0, 0, (lightness * 100).round());
  }

  final saturation = delta / (1 - (2 * lightness - 1).abs());
  final double hue;
  if (high == red) {
    hue = 60 * (((green - blue) / delta) % 6);
  } else if (high == green) {
    hue = 60 * ((blue - red) / delta + 2);
  } else {
    hue = 60 * ((red - green) / delta + 4);
  }

  return (
    hue.round() % 360,
    (saturation * 100).round(),
    (lightness * 100).round()
  );
}

Uint8List colorPng(int color, {int size = 128}) {
  final (r, g, b) = colorRgb(color);
  final row = [
    0,
    for (var x = 0; x < size; x++) ...[r, g, b]
  ];
  final pixels = [for (var y = 0; y < size; y++) ...row];
  final header = [..._uint32(size), ..._uint32(size), 8, 2, 0, 0, 0];

  return Uint8List.fromList([
    ..._pngSignature,
    ..._pngChunk('IHDR', header),
    ..._pngChunk('IDAT', zlib.encode(pixels)),
    ..._pngChunk('IEND', const []),
  ]);
}
