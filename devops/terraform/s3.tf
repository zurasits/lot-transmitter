resource "aws_s3_bucket" "sl-lot-transmitter-s3-bucket" {
  bucket = "sl-${var.PROJECT}${var.S3_POSTFIX}"
  acl = "private"

  tags {
    Name = "sl-${var.PROJECT}"
  }
}

output "s3_bucket" {
  value = "${aws_s3_bucket.sl-lot-transmitter-s3-bucket.id}"
}
