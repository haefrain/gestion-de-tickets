// Lista de comentarios (organismo presentacional). Lista semántica con autor y fecha.
import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemAvatar from '@mui/material/ListItemAvatar';
import ListItemText from '@mui/material/ListItemText';
import Avatar from '@mui/material/Avatar';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import type { Comment } from '../../api/types';
import { formatDateTime } from '../../lib/format';

export interface CommentListProps {
  comments: Comment[];
}

export function CommentList({ comments }: CommentListProps) {
  if (comments.length === 0) {
    return <Typography color="text.secondary">No hay comentarios todavía.</Typography>;
  }

  return (
    <List>
      {comments.map((comment) => (
        <ListItem key={comment.id} alignItems="flex-start" disableGutters>
          <ListItemAvatar>
            <Avatar sx={{ bgcolor: 'primary.main' }}>{comment.authorName.charAt(0)}</Avatar>
          </ListItemAvatar>
          <ListItemText
            primary={
              <Box sx={{ display: 'flex', gap: 1, alignItems: 'baseline', flexWrap: 'wrap' }}>
                <Typography component="span" variant="subtitle2">
                  {comment.authorName}
                </Typography>
                <Typography component="span" variant="caption" color="text.secondary">
                  {formatDateTime(comment.createdAt)}
                </Typography>
              </Box>
            }
            secondary={comment.body}
          />
        </ListItem>
      ))}
    </List>
  );
}
