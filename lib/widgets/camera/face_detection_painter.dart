import 'package:flutter/material.dart';

class FaceDetectionPainter extends CustomPainter {
  final List<Rect> faces;
  final Size imageSize;

  FaceDetectionPainter({
    required this.faces,
    required this.imageSize,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.green
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;

    for (final face in faces) {
      // Scale face rect to screen size
      final scaleX = size.width / imageSize.width;
      final scaleY = size.height / imageSize.height;

      final scaledRect = Rect.fromLTRB(
        face.left * scaleX,
        face.top * scaleY,
        face.right * scaleX,
        face.bottom * scaleY,
      );

      canvas.drawRect(scaledRect, paint);
    }
  }

  @override
  bool shouldRepaint(covariant FaceDetectionPainter oldDelegate) {
    return faces != oldDelegate.faces;
  }
}
